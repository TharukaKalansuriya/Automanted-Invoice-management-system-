// server.js
const express = require('express');
const session = require('express-session');
const cookieParser = require('cookie-parser');
const bcrypt = require('bcrypt');
const crypto = require('crypto');
const path = require('path');
require('dotenv').config();

// Database connection (adjust based on your DB - MySQL, PostgreSQL, etc.)
const Database = require('./database');

const app = express();
const PORT = process.env.PORT || 3000;

// Middleware
app.use(express.json());
app.use(express.urlencoded({ extended: true }));
app.use(cookieParser());
app.use(express.static('public'));

// Session configuration
app.use(session({
  secret: process.env.SESSION_SECRET || crypto.randomBytes(32).toString('hex'),
  resave: false,
  saveUninitialized: false,
  cookie: {
    maxAge: 7200000, // 2 hours in milliseconds
    httpOnly: true,
    secure: process.env.NODE_ENV === 'production', // HTTPS only in production
    sameSite: 'strict'
  }
}));

// Middleware to check if user is authenticated
const isAuthenticated = (req, res, next) => {
  if (req.session.admin_id && req.session.expires && Date.now() < req.session.expires) {
    return next();
  }
  res.redirect('/login');
};

// Middleware to check for remember me cookie
const checkRememberMe = async (req, res, next) => {
  if (!req.session.admin_id && req.cookies.remember_admin) {
    try {
      const db = await Database.getConnection();
      const adminId = Buffer.from(req.cookies.remember_admin, 'base64').toString('utf-8');
      
      const [user] = await db.query(
        'SELECT admin_id, username, full_name, email, role FROM admins WHERE admin_id = ?',
        [adminId]
      );
      
      if (user.length > 0) {
        const userData = user[0];
        req.session.admin_id = userData.admin_id;
        req.session.username = userData.username;
        req.session.full_name = userData.full_name;
        req.session.email = userData.email;
        req.session.role = userData.role;
        req.session.expires = Date.now() + 7200000; // 2 hours
        
        return res.redirect(userData.role === 'super_admin' ? '/super-admin' : '/admin');
      }
    } catch (error) {
      console.error('Remember me check failed:', error);
    }
  }
  next();
};

/**
 * Verify password against multiple hash formats
 */
async function verifyPassword(inputPassword, storedPassword) {
  // Try bcrypt first (recommended)
  try {
    if (await bcrypt.compare(inputPassword, storedPassword)) {
      return true;
    }
  } catch (e) {
    // Not a bcrypt hash, continue to other checks
  }
  
  // Check plain text (legacy support)
  if (inputPassword === storedPassword) {
    return true;
  }
  
  // Check MD5 (legacy support - not recommended)
  const md5Hash = crypto.createHash('md5').update(inputPassword).digest('hex');
  if (md5Hash === storedPassword) {
    return true;
  }
  
  // Check SHA1 (legacy support - not recommended)
  const sha1Hash = crypto.createHash('sha1').update(inputPassword).digest('hex');
  if (sha1Hash === storedPassword) {
    return true;
  }
  
  return false;
}

/**
 * Upgrade legacy password to bcrypt
 */
async function upgradePasswordToHash(db, adminId, plainPassword) {
  try {
    const hashedPassword = await bcrypt.hash(plainPassword, 10);
    await db.query(
      'UPDATE admins SET password = ? WHERE admin_id = ?',
      [hashedPassword, adminId]
    );
  } catch (error) {
    console.error('Password upgrade failed for admin_id:', adminId, error);
  }
}

/**
 * Check if password needs upgrading
 */
function needsPasswordUpgrade(inputPassword, storedPassword) {
  // If it's plain text
  if (inputPassword === storedPassword) return true;
  
  // If it's MD5
  const md5Hash = crypto.createHash('md5').update(inputPassword).digest('hex');
  if (md5Hash === storedPassword) return true;
  
  // If it's SHA1
  const sha1Hash = crypto.createHash('sha1').update(inputPassword).digest('hex');
  if (sha1Hash === storedPassword) return true;
  
  return false;
}

// Routes

// Login page
app.get('/login', checkRememberMe, (req, res) => {
  // If already logged in, redirect based on role
  if (req.session.admin_id && req.session.expires && Date.now() < req.session.expires) {
    const redirectUrl = req.session.role === 'super_admin' ? '/super-admin' : '/admin';
    return res.redirect(redirectUrl);
  }
  
  res.sendFile(path.join(__dirname, 'public', 'login.html'));
});

// Root redirect
app.get('/', (req, res) => {
  res.redirect('/login');
});

// Login POST handler
app.post('/login', async (req, res) => {
  const { email, password, remember_me } = req.body;
  
  // Validation
  if (!email || !password) {
    return res.status(400).json({
      success: false,
      error: 'Please enter both email and password.'
    });
  }
  
  try {
    const db = await Database.getConnection();
    
    // Get user data
    const [users] = await db.query(
      'SELECT admin_id, username, full_name, email, role, password FROM admins WHERE email = ?',
      [email.trim()]
    );
    
    if (users.length === 0) {
      return res.status(401).json({
        success: false,
        error: 'Invalid email or password.'
      });
    }
    
    const user = users[0];
    
    // Verify password
    const isValidPassword = await verifyPassword(password, user.password);
    
    if (!isValidPassword) {
      return res.status(401).json({
        success: false,
        error: 'Invalid email or password.'
      });
    }
    
    // Upgrade password if needed
    if (needsPasswordUpgrade(password, user.password)) {
      await upgradePasswordToHash(db, user.admin_id, password);
    }
    
    // Set session
    req.session.admin_id = user.admin_id;
    req.session.username = user.username;
    req.session.full_name = user.full_name;
    req.session.email = user.email;
    req.session.role = user.role;
    req.session.expires = Date.now() + 7200000; // 2 hours
    
    // Set remember me cookie
    if (remember_me) {
      const token = Buffer.from(user.admin_id.toString()).toString('base64');
      res.cookie('remember_admin', token, {
        maxAge: 2 * 24 * 60 * 60 * 1000, // 2 days
        httpOnly: true,
        secure: process.env.NODE_ENV === 'production',
        sameSite: 'strict'
      });
    }
    
    // Send success response with redirect URL
    const redirectUrl = user.role === 'super_admin' ? '/super-admin' : '/admin';
    res.json({
      success: true,
      redirect: redirectUrl,
      role: user.role
    });
    
  } catch (error) {
    console.error('Login database error:', error);
    res.status(500).json({
      success: false,
      error: 'Database error occurred.'
    });
  }
});

// Logout
app.post('/logout', (req, res) => {
  req.session.destroy((err) => {
    if (err) {
      console.error('Session destruction error:', err);
    }
    res.clearCookie('remember_admin');
    res.json({ success: true, redirect: '/login' });
  });
});

// Protected admin route
app.get('/admin', isAuthenticated, (req, res) => {
  if (req.session.role === 'super_admin') {
    return res.redirect('/super-admin');
  }
  res.sendFile(path.join(__dirname, 'public', 'admin.html'));
});

// Protected super admin route
app.get('/super-admin', isAuthenticated, (req, res) => {
  if (req.session.role !== 'super_admin') {
    return res.redirect('/admin');
  }
  res.sendFile(path.join(__dirname, 'public', 'super-admin.html'));
});

// API endpoint to get current user
app.get('/api/user', isAuthenticated, (req, res) => {
  res.json({
    admin_id: req.session.admin_id,
    username: req.session.username,
    full_name: req.session.full_name,
    email: req.session.email,
    role: req.session.role
  });
});

// Start server
app.listen(PORT, () => {
  console.log(`Server running on http://localhost:${PORT}`);
});