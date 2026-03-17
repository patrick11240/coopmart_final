<?php
// PERFECTLY WORKING EXCEL DOWNLOAD FOR YOUR DATABASE
session_start();
require_once '../include/config.php';

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/admin_login.php");
    exit();
}

// Load PhpSpreadsheet
require_once '../vendor/autoload.php';

// Function to get all customer data with proper columns
function getAllCustomerData($pdo) {
    $data = [];
    
    try {
        // Get all customers from users table
        $query = "SELECT * FROM users WHERE role = 'customer' ORDER BY user_id";
        $stmt = $pdo->query($query);
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($customers)) {
            return [];
        }
        
        foreach ($customers as $customer) {
            // Create row with ALL available data
            $row = [
                'Customer ID' => $customer['user_id'],
                'Full Name' => $customer['full_name'],
                'Email' => $customer['email'],
                'Phone' => $customer['phone'] ?? 'N/A',
                'Gender' => $customer['gender'] ?? 'N/A',
                'Birth Date' => $customer['birth_date'] ?? 'N/A',
                'Address' => $customer['address'] ?? 'N/A',
                'City' => $customer['city'] ?? 'N/A',
                'Province' => $customer['province'] ?? 'N/A',
                'Zip Code' => $customer['zip_code'] ?? 'N/A',
                'Membership Type' => $customer['membership_type'],
                'Role' => $customer['role'],
                'Created Date' => $customer['created_at'],
                'Last Login Date' => $customer['last_login_date'] ?? 'Never',
                'Login Streak' => $customer['login_streak'] ?? 0,
                'Points' => $customer['points'] ?? 0,
                'Daily Spins' => $customer['daily_spins'] ?? 0,
                'Last Spin Date' => $customer['last_spin_date'] ?? 'Never',
                'Email Verified' => $customer['email_verified'] == 1 ? 'Yes' : 'No'
            ];
            
            // Calculate activity metrics
            if ($customer['last_login_date'] && $customer['last_login_date'] != '0000-00-00') {
                $lastLogin = strtotime($customer['last_login_date']);
                $daysSinceLogin = floor((time() - $lastLogin) / (60 * 60 * 24));
                $row['Days Since Last Login'] = $daysSinceLogin;
                
                // Determine churn risk
                if ($customer['last_login_date'] === null || $daysSinceLogin > 90) {
                    $row['Churn Risk'] = 'High';
                    $row['Risk Score'] = 85;
                } elseif ($daysSinceLogin > 30) {
                    $row['Churn Risk'] = 'Medium';
                    $row['Risk Score'] = 60;
                } else {
                    $row['Churn Risk'] = 'Low';
                    $row['Risk Score'] = 25;
                }
            } else {
                $row['Days Since Last Login'] = 'Never';
                $row['Churn Risk'] = 'High';
                $row['Risk Score'] = 90;
            }
            
            // Get order statistics
            try {
                // Total orders
                $orderStmt = $pdo->prepare("SELECT COUNT(*) as order_count FROM orders WHERE user_id = ?");
                $orderStmt->execute([$customer['user_id']]);
                $orderResult = $orderStmt->fetch(PDO::FETCH_ASSOC);
                $row['Total Orders'] = $orderResult['order_count'] ?? 0;
                
                // Total spent
                $spentStmt = $pdo->prepare("SELECT SUM(total_price) as total_spent FROM orders WHERE user_id = ? AND status NOT IN ('canceled')");
                $spentStmt->execute([$customer['user_id']]);
                $spentResult = $spentStmt->fetch(PDO::FETCH_ASSOC);
                $row['Total Spent (Lifetime)'] = $spentResult['total_spent'] ?? 0.00;
                
                // Average order value
                if ($row['Total Orders'] > 0) {
                    $row['Average Order Value'] = round(($row['Total Spent (Lifetime)'] / $row['Total Orders']), 2);
                } else {
                    $row['Average Order Value'] = 0.00;
                }
                
                // Last order date
                $lastOrderStmt = $pdo->prepare("SELECT MAX(created_at) as last_order FROM orders WHERE user_id = ?");
                $lastOrderStmt->execute([$customer['user_id']]);
                $lastOrderResult = $lastOrderStmt->fetch(PDO::FETCH_ASSOC);
                $row['Last Order Date'] = $lastOrderResult['last_order'] ?? 'No orders';
                
            } catch (Exception $e) {
                $row['Total Orders'] = 0;
                $row['Total Spent (Lifetime)'] = 0.00;
                $row['Average Order Value'] = 0.00;
                $row['Last Order Date'] = 'No orders';
            }
            
            // Get cart/abandonment data
            try {
                // Abandoned carts (carts with items but no orders)
                $cartStmt = $pdo->prepare("
                    SELECT COUNT(DISTINCT c.cart_id) as abandoned_carts 
                    FROM carts c 
                    JOIN cart_items ci ON c.cart_id = ci.cart_id 
                    WHERE c.user_id = ? 
                    AND NOT EXISTS (
                        SELECT 1 FROM orders o 
                        WHERE o.user_id = c.user_id 
                        AND o.created_at >= ci.added_at
                    )
                ");
                $cartStmt->execute([$customer['user_id']]);
                $cartResult = $cartStmt->fetch(PDO::FETCH_ASSOC);
                $row['Abandoned Carts'] = $cartResult['abandoned_carts'] ?? 0;
                
            } catch (Exception $e) {
                $row['Abandoned Carts'] = 0;
            }
            
            // Get spin discount usage
            try {
                $spinStmt = $pdo->prepare("SELECT COUNT(*) as spin_count FROM spin_discounts WHERE user_id = ? AND is_used = 1");
                $spinStmt->execute([$customer['user_id']]);
                $spinResult = $spinStmt->fetch(PDO::FETCH_ASSOC);
                $row['Used Spin Discounts'] = $spinResult['spin_count'] ?? 0;
                
            } catch (Exception $e) {
                $row['Used Spin Discounts'] = 0;
            }
            
            // Customer segment based on spending
            if ($row['Total Spent (Lifetime)'] > 5000) {
                $row['Customer Segment'] = 'VIP';
            } elseif ($row['Total Spent (Lifetime)'] > 1000) {
                $row['Customer Segment'] = 'Regular';
            } elseif ($row['Total Orders'] > 0) {
                $row['Customer Segment'] = 'Active';
            } else {
                $row['Customer Segment'] = 'New';
            }
            
            // Engagement score (0-100)
            $engagementScore = 0;
            if ($row['Total Orders'] > 10) $engagementScore += 40;
            elseif ($row['Total Orders'] > 5) $engagementScore += 30;
            elseif ($row['Total Orders'] > 0) $engagementScore += 20;
            
            if ($row['Points'] > 500) $engagementScore += 30;
            elseif ($row['Points'] > 100) $engagementScore += 20;
            elseif ($row['Points'] > 0) $engagementScore += 10;
            
            if ($row['Login Streak'] > 20) $engagementScore += 30;
            elseif ($row['Login Streak'] > 10) $engagementScore += 20;
            elseif ($row['Login Streak'] > 0) $engagementScore += 10;
            
            $row['Engagement Score'] = min($engagementScore, 100);
            
            // Add ML predictions (simplified)
            $mlScore = ($row['Risk Score'] * 0.4) + 
                      (($row['Engagement Score'] < 30 ? 1 : 0) * 30) +
                      (($row['Abandoned Carts'] > 0 ? 1 : 0) * 20) +
                      (($row['Days Since Last Login'] > 60 ? 1 : 0) * 10);
            
            $row['ML Churn Probability'] = round(min($mlScore, 100), 2);
            
            if ($row['ML Churn Probability'] > 70) {
                $row['ML Prediction'] = 'High Risk of Churn';
                $row['Recommendation'] = 'Win-back campaign';
            } elseif ($row['ML Churn Probability'] > 40) {
                $row['ML Prediction'] = 'Medium Risk';
                $row['Recommendation'] = 'Personalized offer';
            } else {
                $row['ML Prediction'] = 'Low Risk';
                $row['Recommendation'] = 'Loyalty reward';
            }
            
            $data[] = $row;
        }
        
        return $data;
        
    } catch (PDOException $e) {
        error_log("Database error in Excel export: " . $e->getMessage());
        return [];
    }
}

// Handle download request
if (isset($_GET['download'])) {
    
    // Get customer data
    $customerData = getAllCustomerData($pdo);
    
    if (empty($customerData)) {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>No Data</title>
            <style>
                body { font-family: Arial; padding: 40px; background: #f0f2f5; }
                .container { max-width: 600px; margin: 50px auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
                h1 { color: #1a4d2e; margin-bottom: 20px; }
                .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0; }
                .btn { display: inline-block; padding: 12px 30px; background: #1a4d2e; color: white; text-decoration: none; border-radius: 5px; margin: 10px; font-weight: bold; }
                .btn:hover { background: #2e7d32; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>📊 Export Customer Data</h1>
                <div class="error">
                    <h3>No Customer Data Found</h3>
                    <p>Your database doesn't contain any customer records with role = 'customer'.</p>
                </div>
                <p>Please add customers to your database first.</p>
                <br>
                <a href="../admin_dashboard.php" class="btn">← Admin Dashboard</a>
                <a href="computation.php" class="btn">← ML Analysis</a>
            </div>
        </body>
        </html>
        <?php
        exit();
    }
    
    try {
        // Create spreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Customer Data');
        
        // Set document properties
        $spreadsheet->getProperties()
            ->setCreator("CoopMart Admin")
            ->setTitle("Customer Database Export")
            ->setDescription("Complete customer data export from CoopMart")
            ->setKeywords("customer data export coopmart");
        
        // Define headers (all columns from our data)
        $headers = array_keys($customerData[0]);
        
        // Write headers with styling
        $col = 'A';
        foreach ($headers as $index => $header) {
            $cell = $col . '1';
            $sheet->setCellValue($cell, $header);
            
            // Apply header styling
            $sheet->getStyle($cell)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 11
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color' => ['rgb' => '1a4d2e']
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'outline' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => '333333']
                    ]
                ]
            ]);
            
            // Set column width based on content
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }
        
        // Write data rows
        $row = 2;
        foreach ($customerData as $customer) {
            $col = 'A';
            foreach ($customer as $key => $value) {
                $cell = $col . $row;
                $sheet->setCellValue($cell, $value);
                
                // Apply conditional formatting for risk levels
                if ($key == 'Churn Risk') {
                    if ($value == 'High') {
                        $sheet->getStyle($cell)->getFill()
                            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('F8D7DA');
                        $sheet->getStyle($cell)->getFont()->setBold(true)->getColor()->setRGB('C62828');
                    } elseif ($value == 'Medium') {
                        $sheet->getStyle($cell)->getFill()
                            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('FFF3CD');
                        $sheet->getStyle($cell)->getFont()->setBold(true)->getColor()->setRGB('856404');
                    } elseif ($value == 'Low') {
                        $sheet->getStyle($cell)->getFill()
                            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('D4EDDA');
                        $sheet->getStyle($cell)->getFont()->setBold(true)->getColor()->setRGB('155724');
                    }
                }
                
                // Format numeric values
                if (is_numeric($value) && !in_array($key, ['Customer ID', 'Points', 'Daily Spins', 'Login Streak', 'Total Orders', 'Abandoned Carts', 'Used Spin Discounts'])) {
                    if (strpos($key, 'Spent') !== false || strpos($key, 'Value') !== false) {
                        $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('"₱"#,##0.00');
                    } elseif (strpos($key, 'Score') !== false || strpos($key, 'Probability') !== false) {
                        $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('0.00"%"');
                    }
                }
                
                // Format date columns
                $dateColumns = ['Birth Date', 'Created Date', 'Last Login Date', 'Last Spin Date', 'Last Order Date'];
                if (in_array($key, $dateColumns) && $value && !in_array($value, ['Never', 'No orders', 'N/A'])) {
                    $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('yyyy-mm-dd');
                }
                
                // Add borders to all cells
                $sheet->getStyle($cell)->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => 'EEEEEE']
                        ]
                    ]
                ]);
                
                $col++;
            }
            
            // Alternate row coloring
            if ($row % 2 == 0) {
                $firstCol = 'A';
                $lastCol = chr(ord($col) - 1);
                $sheet->getStyle($firstCol . $row . ':' . $lastCol . $row)
                    ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('F8F9FA');
            }
            
            $row++;
        }
        
        // Freeze header row
        $sheet->freezePane('A2');
        
        // Create summary sheet
        $summarySheet = $spreadsheet->createSheet();
        $summarySheet->setTitle('Summary');
        
        // Add summary data
        $summarySheet->setCellValue('A1', 'DATASET SUMMARY')->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $summarySheet->setCellValue('A3', 'Export Details:')->getStyle('A3')->getFont()->setBold(true);
        $summarySheet->setCellValue('B3', date('F j, Y, g:i a'));
        
        $summarySheet->setCellValue('A4', 'Total Customers:')->getStyle('A4')->getFont()->setBold(true);
        $summarySheet->setCellValue('B4', count($customerData));
        
        // Calculate statistics
        $totalRevenue = array_sum(array_column($customerData, 'Total Spent (Lifetime)'));
        $avgOrderValue = array_sum(array_column($customerData, 'Average Order Value')) / count($customerData);
        
        $summarySheet->setCellValue('A6', 'Financial Summary:')->getStyle('A6')->getFont()->setBold(true)->setSize(12);
        $summarySheet->setCellValue('A7', 'Total Revenue:')->getStyle('A7')->getFont()->setBold(true);
        $summarySheet->setCellValue('B7', '₱' . number_format($totalRevenue, 2));
        $summarySheet->setCellValue('A8', 'Average Order Value:')->getStyle('A8')->getFont()->setBold(true);
        $summarySheet->setCellValue('B8', '₱' . number_format($avgOrderValue, 2));
        
        // Risk analysis
        $riskCounts = array_count_values(array_column($customerData, 'Churn Risk'));
        $highRisk = $riskCounts['High'] ?? 0;
        $mediumRisk = $riskCounts['Medium'] ?? 0;
        $lowRisk = $riskCounts['Low'] ?? 0;
        
        $summarySheet->setCellValue('A10', 'Churn Risk Analysis:')->getStyle('A10')->getFont()->setBold(true)->setSize(12);
        $summarySheet->setCellValue('A11', 'High Risk:')->getStyle('A11')->getFont()->setBold(true);
        $summarySheet->setCellValue('B11', $highRisk . ' customers');
        $summarySheet->setCellValue('A12', 'Medium Risk:')->getStyle('A12')->getFont()->setBold(true);
        $summarySheet->setCellValue('B12', $mediumRisk . ' customers');
        $summarySheet->setCellValue('A13', 'Low Risk:')->getStyle('A13')->getFont()->setBold(true);
        $summarySheet->setCellValue('B13', $lowRisk . ' customers');
        
        // Format summary sheet
        $summarySheet->getColumnDimension('A')->setWidth(25);
        $summarySheet->getColumnDimension('B')->setWidth(25);
        
        // Add notes
        $summarySheet->setCellValue('A15', 'Notes:')->getStyle('A15')->getFont()->setBold(true);
        $summarySheet->setCellValue('A16', '• Data exported from CoopMart database');
        $summarySheet->setCellValue('A17', '• Includes ML-based churn predictions');
        $summarySheet->setCellValue('A18', '• Use for customer retention strategies');
        
        // Set active sheet back to data
        $spreadsheet->setActiveSheetIndex(0);
        
        // Set filename
        $filename = 'CoopMart_Customer_Database_' . date('Y-m-d_H-i') . '.xlsx';
        
        // Output Excel file
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit();
        
    } catch (Exception $e) {
        // If Excel fails, fallback to CSV
        error_log("Excel export error: " . $e->getMessage());
        
        // Output as CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="customers_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Write UTF-8 BOM for Excel
        fwrite($output, "\xEF\xBB\xBF");
        
        // Write headers
        fputcsv($output, array_keys($customerData[0]));
        
        // Write data
        foreach ($customerData as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit();
    }
}

// Show download page if no download parameter
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Customer Data - CoopMart</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        
        h1 {
            color: #1a4d2e;
            margin-bottom: 20px;
            font-size: 28px;
        }
        
        h1 i {
            margin-right: 10px;
        }
        
        p {
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .download-btn {
            display: inline-block;
            padding: 15px 40px;
            background: linear-gradient(135deg, #1a4d2e, #3a754f);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: bold;
            transition: all 0.3s;
            margin-bottom: 20px;
        }
        
        .download-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(26, 77, 46, 0.3);
        }
        
        .download-btn i {
            margin-right: 10px;
        }
        
        .info-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            text-align: left;
        }
        
        .info-box h3 {
            color: #1a4d2e;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .info-box ul {
            list-style: none;
            padding-left: 0;
        }
        
        .info-box li {
            padding: 5px 0;
            color: #666;
            font-size: 14px;
        }
        
        .info-box li i {
            color: #1a4d2e;
            margin-right: 8px;
            width: 20px;
        }
        
        .back-links {
            margin-top: 30px;
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        
        .back-link {
            color: #666;
            text-decoration: none;
            font-size: 14px;
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .back-link:hover {
            background: #f8f9fa;
            color: #1a4d2e;
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 20px;
            }
            
            h1 {
                font-size: 24px;
            }
            
            .back-links {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-file-excel"></i> Export Customer Database</h1>
        <p>Download complete customer information in Excel format with advanced analytics and ML predictions.</p>
        
        <a href="?download=1" class="download-btn">
            <i class="fas fa-download"></i> Download Excel File (.xlsx)
        </a>
        
        <div class="info-box">
            <h3><i class="fas fa-check-circle"></i> File Includes:</h3>
            <ul>
                <li><i class="fas fa-user"></i> Customer demographics & contact info</li>
                <li><i class="fas fa-shopping-cart"></i> Order history & spending patterns</li>
                <li><i class="fas fa-chart-line"></i> Activity metrics & engagement scores</li>
                <li><i class="fas fa-brain"></i> ML-based churn predictions</li>
                <li><i class="fas fa-lightbulb"></i> Personalized recommendations</li>
                <li><i class="fas fa-file-alt"></i> Summary statistics sheet</li>
            </ul>
        </div>
        
        <div class="back-links">
            <a href="../admin_dashboard.php" class="back-link">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="computation.php" class="back-link">
                <i class="fas fa-calculator"></i> ML Analysis
            </a>
            <a href="analytics.php" class="back-link">
                <i class="fas fa-chart-bar"></i> Analytics
            </a>
        </div>
    </div>
</body>
</html>