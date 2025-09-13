<?php
// File: SalesReport.php
include '../components/connect.php';
session_start();

if(!isset($_SESSION['admin_id'])){
    header('location:admin_login.php');
    exit();
}

/**
 * SalesReport class to handle sales reporting functionality
 */
class SalesReport {
    private $conn;
    private $sales;
    private $totalRevenue;
    private $monthlySales;
    
    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
        $this->sales = [];
        $this->totalRevenue = 0;
        $this->monthlySales = [];
    }
    
    /**
     * Fetch sales data from database
     */
    public function fetchSalesData() {
        $selectSales = $this->conn->prepare("SELECT * FROM `orders` WHERE payment_status = 'completed' ORDER BY placed_on DESC");
        $selectSales->execute();
        $this->sales = $selectSales->fetchAll(PDO::FETCH_ASSOC);
        
        $this->calculateTotalRevenue();
        $this->calculateMonthlySales();
    }
    
    /**
     * Calculate total revenue from sales
     */
    private function calculateTotalRevenue() {
        $this->totalRevenue = 0;
        foreach($this->sales as $sale) {
            $this->totalRevenue += $sale['total_price'];
        }
    }
    
    /**
     * Calculate monthly sales data
     */
    private function calculateMonthlySales() {
        $this->monthlySales = [];
        
        for ($i = 1; $i <= 12; $i++) {
            $month = date('Y-m', strtotime(date('Y') . "-" . $i . "-01"));
            $startDate = $month . "-01";
            $endDate = date('Y-m-t', strtotime($startDate));
            
            $selectMonthlySales = $this->conn->prepare("SELECT SUM(total_price) as total FROM `orders` WHERE payment_status = 'completed' AND placed_on BETWEEN ? AND ?");
            $selectMonthlySales->execute([$startDate, $endDate]);
            $monthlyData = $selectMonthlySales->fetch(PDO::FETCH_ASSOC);
            
            $this->monthlySales[$i] = [
                'month' => date('M', strtotime($startDate)),
                'total' => $monthlyData['total'] ?: 0
            ];
        }
    }
    
    /**
     * Generate and download sales report
     */
    public function downloadReport() {
        // Set headers for file download
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="sales_report_'.date('Ymd_His').'.txt"');
        
        // Generate the report content
        echo "Nepal~Store Sales Report\n";
        echo "Generated on: " . date('Y-m-d H:i:s') . "\n";
        echo "==========================================\n\n";
        
        echo "SUMMARY:\n";
        echo "Total Revenue: रु" . number_format($this->totalRevenue) . "\n";
        echo "Total Orders: " . count($this->sales) . "\n\n";
        
        echo "MONTHLY SALES:\n";
        echo "------------------------------------------\n";
        echo str_pad("Month", 15) . str_pad("Revenue", 20) . "\n";
        echo "------------------------------------------\n";
        foreach($this->monthlySales as $monthly) {
            echo str_pad($monthly['month'], 15) . 
                 str_pad("रु" . number_format($monthly['total']), 20) . "\n";
        }
        echo "\n";
        
        echo "RECENT ORDERS (Last 20):\n";
        echo "==========================================\n";
        echo str_pad("Order ID", 12) . str_pad("User ID", 12) . str_pad("Total Price", 15) . str_pad("Date", 20) . "\n";
        echo "------------------------------------------\n";
        $counter = 0;
        foreach($this->sales as $sale){
            if($counter++ >= 20) break;
            echo str_pad($sale['id'], 12) . 
                 str_pad($sale['user_id'], 12) . 
                 str_pad("रु" . number_format($sale['total_price']), 15) . 
                 str_pad($sale['placed_on'], 20) . "\n";
        }
        
        exit();
    }
    
    // Getters for accessing data in views
    public function getSales() {
        return $this->sales;
    }
    
    public function getTotalRevenue() {
        return $this->totalRevenue;
    }
    
    public function getMonthlySales() {
        return $this->monthlySales;
    }
}

// Create SalesReport instance and fetch data
$salesReport = new SalesReport($conn);
$salesReport->fetchSalesData();

// Handle download request
if(isset($_POST['download_report'])){
    $salesReport->downloadReport();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        :root {
            --main-color: #2980b9;
            --secondary-color: #3498db;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
            --success-color: #27ae60;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: #333;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .heading {
            text-align: center;
            color: var(--main-color);
            margin-bottom: 30px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--light-color);
        }
        
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .summary-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .summary-card h3 {
            font-size: 1.8rem;
            margin-bottom: 10px;
            color: var(--main-color);
        }
        
        .summary-card.revenue h3 {
            color: var(--success-color);
        }
        
        .btn {
            display: inline-block;
            padding: 12px 25px;
            background: var(--main-color);
            color: white;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            margin: 10px 5px;
            font-size: 1rem;
        }
        
        .btn:hover {
            background: var(--secondary-color);
            transform: scale(1.05);
        }
        
        .btn-download {
            background: var(--dark-color);
        }
        
        .btn-download:hover {
            background: #1a252f;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        table, th, td {
            border: 1px solid #ddd;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
        }
        
        th {
            background-color: var(--main-color);
            color: white;
        }
        
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        
        tr:hover {
            background-color: #e9f7fe;
        }
        
        .text-center {
            text-align: center;
        }
        
        .mt-20 {
            margin-top: 20px;
        }
        
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin: 20px 0;
        }
        
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .chart-title {
            text-align: center;
            color: var(--main-color);
            margin-bottom: 15px;
        }
        
        @media (max-width: 768px) {
            .summary-cards {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                max-width: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="heading">Nepal~Store---Sales Report</h1>
        
        <div class="summary-cards">
            <div class="summary-card revenue">
                <h3>रु<?= number_format($salesReport->getTotalRevenue()) ?></h3>
                <p>Total Revenue</p>
            </div>
            <div class="summary-card">
                <h3><?= count($salesReport->getSales()) ?></h3>
                <p>Total Orders</p>
            </div>
        </div>
        
        <div class="action-buttons">
            <form method="post">
                <button type="submit" name="download_report" class="btn btn-download">
                    <i class="fas fa-download"></i> Download Report
                </button>
            </form>
            <a href="dashboard.php" class="btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
        
        <div class="chart-container">
            <h2 class="chart-title">Monthly Sales</h2>
            <table>
                <tr>
                    <th>Month</th>
                    <th>Revenue</th>
                </tr>
                <?php foreach($salesReport->getMonthlySales() as $monthly): ?>
                <tr>
                    <td><?= $monthly['month'] ?></td>
                    <td>रु<?= number_format($monthly['total']) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        
        <h2 class="text-center">Recent Orders</h2>
        <table>
            <tr>
                <th>Order ID</th>
                <th>User ID</th>
                <th>Total Price</th>
                <th>Date</th>
            </tr>
            <?php 
            $counter = 0;
            foreach($salesReport->getSales() as $sale): 
                if($counter++ >= 20) break;
            ?>
            <tr>
                <td><?= $sale['id'] ?></td>
                <td><?= $sale['user_id'] ?></td>
                <td>रु<?= number_format($sale['total_price']) ?></td>
                <td><?= $sale['placed_on'] ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        
        <div class="action-buttons mt-20">
            <form method="post">
                <button type="submit" name="download_report" class="btn btn-download">
                    <i class="fas fa-download"></i> Download Report
                </button>
            </form>
        </div>
    </div>
</body>
</html>