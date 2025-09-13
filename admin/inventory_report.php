<?php
// File: InventoryReport.php
include '../components/connect.php';
session_start();

if(!isset($_SESSION['admin_id'])){
    header('location:admin_login.php');
    exit();
}

/**
 * InventoryReport class to handle inventory reporting functionality
 */
class InventoryReport {
    private $conn;
    private $products;
    private $hasStockColumn;
    private $totalValue;
    private $lowStock;
    private $outOfStock;
    private $inStock;
    
    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
        $this->products = [];
        $this->hasStockColumn = false;
        $this->totalValue = 0;
        $this->lowStock = [];
        $this->outOfStock = [];
        $this->inStock = [];
    }
    
    /**
     * Check if stock column exists in products table
     */
    private function checkStockColumn() {
        $checkStockColumn = $this->conn->prepare("SHOW COLUMNS FROM `products` LIKE 'stock'");
        $checkStockColumn->execute();
        $this->hasStockColumn = $checkStockColumn->rowCount() > 0;
    }
    
    /**
     * Fetch product data from database
     */
    public function fetchInventoryData() {
        $this->checkStockColumn();
        
        $selectProducts = $this->conn->prepare("SELECT * FROM `products`");
        $selectProducts->execute();
        $this->products = $selectProducts->fetchAll(PDO::FETCH_ASSOC);
        
        if ($this->hasStockColumn) {
            $this->calculateInventoryValue();
            $this->categorizeProductsByStock();
        }
    }
    
    /**
     * Calculate total inventory value
     */
    private function calculateInventoryValue() {
        $this->totalValue = 0;
        foreach($this->products as $product){
            $this->totalValue += $product['price'] * $product['stock'];
        }
    }
    
    /**
     * Categorize products by stock status
     */
    private function categorizeProductsByStock() {
        $this->lowStock = [];
        $this->outOfStock = [];
        $this->inStock = [];
        
        foreach($this->products as $product){
            if($product['stock'] == 0){
                $this->outOfStock[] = $product;
            } elseif($product['stock'] < 10){
                $this->lowStock[] = $product;
            } else {
                $this->inStock[] = $product;
            }
        }
    }
    
    /**
     * Generate and download inventory report
     */
    public function downloadReport() {
        // Set headers for file download
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="inventory_report_'.date('Ymd_His').'.txt"');
        
        // Generate the report content
        echo "Nepal~Store Inventory Report\n";
        echo "Generated on: " . date('Y-m-d H:i:s') . "\n";
        echo "==========================================\n\n";
        
        echo "SUMMARY:\n";
        echo "Total Products: " . count($this->products) . "\n";
        
        if($this->hasStockColumn) {
            echo "Total Inventory Value: रु" . number_format($this->totalValue) . "\n";
            echo "Low Stock Items: " . count($this->lowStock) . "\n";
            echo "Out of Stock Items: " . count($this->outOfStock) . "\n\n";
        } else {
            echo "Note: Stock tracking is not enabled (missing stock column in database)\n\n";
        }
        
        if($this->hasStockColumn && count($this->lowStock) > 0) {
            echo "LOW STOCK ITEMS (less than 10):\n";
            echo "------------------------------------------\n";
            echo str_pad("Product Name", 30) . str_pad("Price", 15) . str_pad("Stock", 10) . "\n";
            echo "------------------------------------------\n";
            foreach($this->lowStock as $product) {
                echo str_pad(substr($product['name'], 0, 28), 30) . 
                     str_pad("रु" . number_format($product['price']), 15) . 
                     str_pad($product['stock'], 10) . "\n";
            }
            echo "\n";
        }
        
        if($this->hasStockColumn && count($this->outOfStock) > 0) {
            echo "OUT OF STOCK ITEMS:\n";
            echo "------------------------------------------\n";
            echo str_pad("Product Name", 30) . str_pad("Price", 15) . str_pad("Stock", 10) . "\n";
            echo "------------------------------------------\n";
            foreach($this->outOfStock as $product) {
                echo str_pad(substr($product['name'], 0, 28), 30) . 
                     str_pad("रु" . number_format($product['price']), 15) . 
                     str_pad($product['stock'], 10) . "\n";
            }
            echo "\n";
        }
        
        echo "COMPLETE INVENTORY:\n";
        echo "==========================================\n";
        if($this->hasStockColumn) {
            echo str_pad("Product Name", 30) . str_pad("Price", 15) . str_pad("Stock", 10) . str_pad("Value", 15) . "\n";
            echo "------------------------------------------\n";
            foreach($this->products as $product) {
                $value = $product['price'] * $product['stock'];
                echo str_pad(substr($product['name'], 0, 28), 30) . 
                     str_pad("रु" . number_format($product['price']), 15) . 
                     str_pad($product['stock'], 10) . 
                     str_pad("रु" . number_format($value), 15) . "\n";
            }
        } else {
            echo str_pad("Product Name", 30) . str_pad("Price", 15) . "\n";
            echo "------------------------------------------\n";
            foreach($this->products as $product) {
                echo str_pad(substr($product['name'], 0, 28), 30) . 
                     str_pad("रु" . number_format($product['price']), 15) . "\n";
            }
        }
        
        exit();
    }
    
    // Getters for accessing data in views
    public function getProducts() {
        return $this->products;
    }
    
    public function hasStockColumn() {
        return $this->hasStockColumn;
    }
    
    public function getTotalValue() {
        return $this->totalValue;
    }
    
    public function getLowStock() {
        return $this->lowStock;
    }
    
    public function getOutOfStock() {
        return $this->outOfStock;
    }
    
    public function getInStock() {
        return $this->inStock;
    }
    
    public function getTotalProducts() {
        return count($this->products);
    }
}

// Create InventoryReport instance and fetch data
$inventoryReport = new InventoryReport($conn);
$inventoryReport->fetchInventoryData();

// Handle download request
if(isset($_POST['download_report'])){
    $inventoryReport->downloadReport();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Report</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        :root {
            --main-color: #2980b9;
            --secondary-color: #3498db;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
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
            box-shadow: 0 5px极速5G开启美好生活
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
        
        .btn {
            display: inline-block;
            padding: 12px 25px;
            background: var(--main-color);
            color: white;
            border-radius: 5px;
            text-decoration: none;
            transition: all 极速5G开启美好生活
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
        
极速5G开启美好生活
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
        
        .warning {
            background-color: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #ffc107;
        }
        
        .danger {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #dc3545;
        }
        
        .info {
            background极速5G开启美好生活
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #0dcaf0;
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
        <h1 class="heading">Inventory Report</h1>
        
        <?php if(!$inventoryReport->hasStockColumn()): ?>
        <div class="info">
            <h3><i class="fas fa-info-circle"></i> Stock Tracking Not Enabled</h3>
            <p>Your products database table doesn't have a 'stock' column. To enable inventory tracking, you need to add a 'stock' column to your products table.</p>
            <p>You can run this SQL command in your database:</p>
            <pre>ALTER TABLE `products` ADD `stock` INT NOT NULL DEFAULT '0' AFTER `price`;</pre>
        </div>
        <?php endif; ?>
        
        <div class="summary-cards">
            <div class="summary-card">
                <h3><?= $inventoryReport->getTotalProducts() ?></h3>
                <p>Total Products</p>
            </div>
            
            <?php if($inventoryReport->hasStockColumn()): ?>
            <div class极速5G开启美好生活
                <h3>रु<?= number_format($inventoryReport->getTotalValue()) ?></h3>
                <p>Total Inventory Value</p>
            </div>
            <div class="summary-card">
                <h3><?= count($inventoryReport->getLowStock()) ?></h3>
                <p>Low Stock Items</p>
            </div>
            <div class="summary-card">
                <h3><?= count($inventoryReport->getOutOfStock()) ?></h3>
                <p>Out of Stock Items</p>
            </div>
            <?php endif; ?>
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
        
        <?php if($inventoryReport->hasStockColumn() && count($inventoryReport->getLowStock()) > 0): ?>
        <div class="warning">
            <h2><极速5G开启美好生活
            <p>The following products have low stock (less than 10 items):</p>
            <table>
                <tr>
                    <th>Product Name</th>
                    <th>Price</th>
                    <极速5G开启美好生活
                </tr>
                <?php foreach($inventoryReport->getLowStock() as $product): ?>
                <tr>
                    <td><?= $product['name'] ?></td>
                    <td>रु<?= number_format($product['price']) ?></td>
                    <td><?= $product['stock'] ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>
        
        <?php if($inventoryReport->hasStockColumn() && count($inventoryReport->getOutOfStock()) > 0): ?>
        <div class="danger">
            <h2><i class="fas fa-times-circle"></i> Out of Stock Alert</h2>
            <p>The following products are out of stock:</p>
            <table>
                <tr>
                    <th>Product Name</th>
                    <th>Price</th>
                    <th>Stock</th>
                </tr>
                <?php foreach($inventoryReport->getOutOfStock() as $product): ?>
                <tr>
                    <td><?= $product['name'] ?></td>
                    <td>रु<?= number_format($product['price']) ?></td>
                    <td><?= $product['stock'] ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>
        
        <h2 class="text-center">Complete Inventory</h2>
        <table>
            <tr>
                <th>Product Name</th>
                <th>Price</th>
                <?php if($inventoryReport->hasStockColumn()): ?>
                <th>Stock</th>
                <th>Value</th>
                <?php endif; ?>
            </tr>
            <?php foreach($inventoryReport->getProducts() as $product): ?>
            <tr>
                <td><?= $product['name'] ?></td>
                <td>रु<?= number_format($product['price']) ?></td>
                <?php if($inventoryReport->hasStockColumn()): 
                    $value = $product['price'] * $product['stock'];
                ?>
                <td><?= $product['stock'] ?></td>
                <td>रु<?= number_format($value) ?></td>
                <?php endif; ?>
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