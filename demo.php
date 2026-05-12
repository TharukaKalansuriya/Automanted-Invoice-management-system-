<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Fix Test</title>
    
    <!-- FIXED: Load jsPDF from CDN with proper error handling -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js" 
            onerror="console.error('Failed to load jsPDF'); window.jsPDFLoadError = true;"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.plugin.autotable.min.js" 
            onerror="console.error('Failed to load autoTable'); window.autoTableLoadError = true;"></script>
    
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .test-section { margin: 20px 0; padding: 20px; border: 1px solid #ccc; border-radius: 5px; }
        button { padding: 10px 20px; margin: 10px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .error { color: red; font-weight: bold; }
        .success { color: green; font-weight: bold; }
        .loading { color: orange; }
    </style>
</head>
<body>
    <h1>PDF Generation Fix</h1>
    
    <div class="test-section">
        <h3>Library Status Check</h3>
        <div id="libraryStatus" class="loading">Checking libraries...</div>
        <button onclick="checkLibraries()">Recheck Libraries</button>
    </div>
    
    <div class="test-section">
        <h3>Test PDF Generation</h3>
        <button onclick="testPDFGeneration()">Test Simple PDF</button>
        <button onclick="testAdvancedPDF()">Test Advanced PDF with Table</button>
        <div id="testResult"></div>
    </div>
    
    <div class="test-section">
        <h3>Fixed Code for Your Application</h3>
        <p>Replace the script loading section in your HTML head with:</p>
        <pre style="background: #f5f5f5; padding: 10px; border-radius: 5px;">
&lt;!-- Replace your existing jsPDF script tags with these --&gt;
&lt;script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js" 
        onerror="console.error('Failed to load jsPDF'); window.jsPDFLoadError = true;"&gt;&lt;/script&gt;
&lt;script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.plugin.autotable.min.js" 
        onerror="console.error('Failed to load autoTable'); window.autoTableLoadError = true;"&gt;&lt;/script&gt;
        </pre>
    </div>

    <script>
        // FIXED: Library checking function
        function checkLibraries() {
            const statusDiv = document.getElementById('libraryStatus');
            let status = '';
            
            // Check for load errors first
            if (window.jsPDFLoadError) {
                status += '<div class="error">❌ jsPDF failed to load from CDN</div>';
            } else if (typeof window.jsPDF === 'undefined') {
                status += '<div class="error">❌ jsPDF not found</div>';
            } else if (window.jsPDF && window.jsPDF.jsPDF) {
                status += '<div class="success">✅ jsPDF loaded successfully</div>';
            } else {
                status += '<div class="error">❌ jsPDF structure incorrect</div>';
            }
            
            if (window.autoTableLoadError) {
                status += '<div class="error">❌ autoTable failed to load from CDN</div>';
            } else {
                try {
                    const testDoc = new window.jsPDF.jsPDF();
                    if (testDoc.autoTable) {
                        status += '<div class="success">✅ autoTable plugin loaded successfully</div>';
                    } else {
                        status += '<div class="error">❌ autoTable plugin not available</div>';
                    }
                } catch (e) {
                    status += '<div class="error">❌ Cannot test autoTable: ' + e.message + '</div>';
                }
            }
            
            statusDiv.innerHTML = status;
        }

        // FIXED: Simple PDF test
        function testPDFGeneration() {
            const resultDiv = document.getElementById('testResult');
            
            try {
                if (typeof window.jsPDF === 'undefined') {
                    throw new Error('jsPDF not loaded');
                }
                
                const { jsPDF } = window.jsPDF;
                const doc = new jsPDF();
                
                // Test basic text
                doc.setFontSize(16);
                doc.text('Test PDF Generation', 20, 20);
                doc.setFontSize(12);
                doc.text('If you can see this, basic PDF generation works!', 20, 40);
                doc.text('Generated at: ' + new Date().toLocaleString(), 20, 60);
                
                // Save the test PDF
                doc.save('test_basic.pdf');
                
                resultDiv.innerHTML = '<div class="success">✅ Basic PDF generated successfully!</div>';
            } catch (error) {
                resultDiv.innerHTML = '<div class="error">❌ PDF Generation failed: ' + error.message + '</div>';
                console.error('PDF Error:', error);
            }
        }

        // FIXED: Advanced PDF test with table
        function testAdvancedPDF() {
            const resultDiv = document.getElementById('testResult');
            
            try {
                if (typeof window.jsPDF === 'undefined') {
                    throw new Error('jsPDF not loaded');
                }
                
                const { jsPDF } = window.jsPDF;
                const doc = new jsPDF();
                
                if (!doc.autoTable) {
                    throw new Error('autoTable plugin not loaded');
                }
                
                // Test header
                doc.setFontSize(16);
                doc.setTextColor(40, 116, 166);
                doc.text('Advanced PDF Test', 20, 20);
                
                // Test table
                doc.autoTable({
                    startY: 30,
                    head: [['Service', 'Quantity', 'Price', 'Total']],
                    body: [
                        ['Web Development', '1', '$500.00', '$500.00'],
                        ['SEO Optimization', '2', '$150.00', '$300.00'],
                        ['Social Media Management', '3', '$100.00', '$300.00']
                    ],
                    foot: [['', '', 'Total:', '$1,100.00']],
                    headStyles: { 
                        fillColor: [40, 116, 166],
                        textColor: [255, 255, 255]
                    },
                    footStyles: { 
                        fillColor: [240, 240, 240], 
                        textColor: [0, 0, 0], 
                        fontStyle: 'bold'
                    }
                });
                
                // Add footer
                doc.setFontSize(10);
                doc.setTextColor(100, 100, 100);
                doc.text('Generated at: ' + new Date().toLocaleString(), 20, doc.lastAutoTable.finalY + 20);
                
                // Save the test PDF
                doc.save('test_advanced.pdf');
                
                resultDiv.innerHTML = '<div class="success">✅ Advanced PDF with table generated successfully!</div>';
            } catch (error) {
                resultDiv.innerHTML = '<div class="error">❌ Advanced PDF Generation failed: ' + error.message + '</div>';
                console.error('Advanced PDF Error:', error);
            }
        }

        // FIXED: Enhanced downloadPDF function for your application
        function createFixedDownloadPDF() {
            return function downloadPDF() {
                if (!currentInvoice) {
                    alert('No invoice data available');
                    return;
                }
                
                try {
                    // Enhanced library checking
                    if (typeof window.jsPDF === 'undefined') {
                        throw new Error('jsPDF library not loaded. Please refresh the page.');
                    }
                    
                    const { jsPDF } = window.jsPDF;
                    if (!jsPDF) {
                        throw new Error('jsPDF constructor not available');
                    }
                    
                    const doc = new jsPDF();
                    
                    if (!doc.autoTable) {
                        throw new Error('autoTable plugin not loaded. Please refresh the page.');
                    }
                    
                    // Generate the PDF content
                    generatePDFContent(doc);
                    
                } catch (error) {
                    console.error('PDF Download Error:', error);
                    alert('PDF generation failed: ' + error.message + '\n\nTry refreshing the page and ensure you have an internet connection.');
                }
            };
        }

        // Auto-check libraries when page loads
        window.addEventListener('load', function() {
            setTimeout(checkLibraries, 1000); // Give time for libraries to load
        });
        
        // Manual check after a delay
        setTimeout(checkLibraries, 2000);
    </script>
</body>
</html>