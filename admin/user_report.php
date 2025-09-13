<?php
// File: UserReport.php
include '../components/connect.php';
session_start();

if(!isset($_SESSION['admin_id'])){
    header('location:admin_login.php');
    exit();
}

/**
 * UserReport class to handle user reporting functionality
 */
class UserReport {
    private $conn;
    private $users;
    private $monthlyRegistrations;
    
    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
        $this->users = [];
        $this->monthlyRegistrations = [];
    }
    
    /**
     * Fetch user data from database
     */
    public function fetchUserData() {
        $selectUsers = $this->conn->prepare("SELECT * FROM `users`");
        $selectUsers->execute();
        $this->users = $selectUsers->fetchAll(PDO::FETCH_ASSOC);
        
        $this->calculateMonthlyRegistrations();
    }
    
    /**
     * Calculate monthly registration data
     */
    private function calculateMonthlyRegistrations() {
        $this->monthlyRegistrations = [];
        
        for ($i = 1; $i <= 12; $i++) {
            $month = date('Y-m', strtotime(date('Y') . "-" . $i . "-01"));
            $startDate = $month . "-01";
            $endDate = date('Y-m-t', strtotime($startDate));
            
            $selectMonthlyUsers = $this->conn->prepare("SELECT COUNT(*) as count FROM `users` WHERE created_at BETWEEN ? AND ?");
            $selectMonthlyUsers->execute([$startDate, $endDate]);
            $monthlyData = $selectMonthlyUsers->fetch(PDO::FETCH_ASSOC);
            
            $this->monthlyRegistrations[$i] = [
                'month' => date('M', strtotime($startDate)),
                'count' => $monthlyData['count'] ?: 0
            ];
        }
    }
    
    /**
     * Generate and download user report
     */
    public function downloadReport() {
        // Set headers for file download
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="user_report_'.date('Ymd_His').'.txt"');
        
        // Generate the report content
        echo "Nepal~Store User Report\n";
        echo "Generated on: " . date('Y-m-d H:i:s') . "\n";
        echo "==========================================\n\n";
        
        echo "SUMMARY:\n";
        echo "Total Users: " . count($this->users) . "\n\n";
        
        echo "MONTHLY REGISTRATIONS:\n";
        echo "------------------------------------------\n";
        echo str_pad("Month", 15) . str_pad("Registrations", 15) . "\n";
        echo "------------------------------------------\n";
        foreach($this->monthlyRegistrations as $monthly) {
            echo str_pad($monthly['month'], 15) . 
                 str_pad($monthly['count'], 15) . "\n";
        }
        echo "\n";
        
        echo "USER LIST:\n";
        echo "==========================================\n";
        echo str_pad("User ID", 10) . str_pad("Name", 25) . str_pad("Email", 30) . str_pad("Registration Date", 20) . "\n";
        echo "------------------------------------------\n";
        foreach($this->users as $user) {
            echo str_pad($user['id'], 10) . 
                 str_pad(substr($user['name'], 0, 23), 25) . 
                 str_pad(substr($user['email'], 0, 28), 30) . 
                 str_pad($user['created_at'], 20) . "\n";
        }
        
        exit();
    }
    
    // Getters for accessing data in views
    public function getUsers() {
        return $this->users;
    }
    
    public function getMonthlyRegistrations() {
        return $this->monthlyRegistrations;
    }
    
    public function getTotalUsers() {
        return count($this->users);
    }
}

// Create UserReport instance and fetch data
$userReport = new UserReport($conn);
$userReport->fetchUserData();

// Handle download request
if(isset($_POST['download_report'])){
    $userReport->downloadReport();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Report</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        :root {
            --main-color: #2980b9;
            --secondary-color: #3498db;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
            --purple-color: #8e44ad;
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
        
        .summary-card.users h3 {
            color: var(--purple-color);
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
            
            table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="heading">User Report</h1>
        
        <div class="summary-cards">
            <div class="summary-card users">
                <h3><?= $userReport->getTotalUsers() ?></h3>
                <p>Total Users</p>
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
            <h2 class="chart-title">Monthly Registrations</h2>
            <table>
                <tr>
                    <th>Month</th>
                    <th>Registrations</th>
                </tr>
                <?php foreach($userReport->getMonthlyRegistrations() as $monthly): ?>
                <tr>
                    <td><?= $monthly['month'] ?></td>
                    <td><?= $monthly['count'] ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        
        <h2 class="text-center">User List</h2>
        <table>
            <tr>
                <th>User ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Registration Date</th>
            </tr>
            <?php foreach($userReport->getUsers() as $user): ?>
            <tr>
                <td><?= $user['id'] ?></td>
                <td><?= $user['name'] ?></td>
                <td><?= $user['email'] ?></td>
                <td><?= $user['created_at'] ?></td>
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