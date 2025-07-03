<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "student1";

// Connect to database
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create students table if it doesn't exist
$createTable = "
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    school VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($createTable);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['delete_id'])) {
        $delete_id = $_POST['delete_id'];
        $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        $stmt->close();
    } else {
        $name = trim($_POST['name']);
        $school = trim($_POST['school']);
        $amount = trim($_POST['amount']);

        if ($name && $school && $amount) {
            // Validate amount is a valid number
            if (!is_numeric($amount) || $amount < 0) {
                echo "<script>alert('Please enter a valid amount.');</script>";
            } else {
                $stmt = $conn->prepare("INSERT INTO students (name, school, amount) VALUES (?, ?, ?)");
                $stmt->bind_param("ssd", $name, $school, $amount);
                if ($stmt->execute()) {
                    echo "<script>alert('Student added successfully!');</script>";
                } else {
                    echo "<script>alert('Error adding student.');</script>";
                }
                $stmt->close();
            }
        }
    }
}

// Handle search query
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($search_query) {
    $search_query = "%" . $conn->real_escape_string($search_query) . "%";
    $sql = "SELECT * FROM students WHERE name LIKE ? OR school LIKE ? ORDER BY id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $search_query, $search_query);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    // Fetch all students for the dashboard
    $result = $conn->query("SELECT * FROM students ORDER BY id DESC");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #f05462;
            --light-bg: #f4f9ff;
            --white: #fff;
            --danger: #f05462;
            --search-bg: #f1f1f1;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            min-height: 100vh;
            background: var(--light-bg);
        }

        a {
            text-decoration: none;
        }

        li {
            list-style: none;
        }

        .btn {
            background: var(--primary);
            color: #fff;
            padding: 6px 14px;
            text-align: center;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-weight: 500;
        }

        .btn:hover {
            color: var(--primary);
            background: #fff;
            border: 2px solid var(--primary);
        }

        .btn-danger {
            background: var(--danger);
            margin-left: 5px;
        }

        .btn-danger:hover {
            background: #fff;
            color: var(--danger);
            border: 2px solid var(--danger);
        }

        .btn-clear {
            background: #999;
            margin-left: 5px;
        }

        .btn-clear:hover {
            background: #fff;
            color: #999;
            border: 2px solid #999;
        }

        .side-menu {
            position: fixed;
            background-color: var(--primary);
            width: 250px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding-top: 20px;
            transition: width 0.3s;
            overflow: hidden;
        }

        .side-menu.collapsed {
            width: 70px;
        }

        .side-menu ul li {
            font-size: 18px;
            padding: 15px 30px;
            color: #fff;
            display: flex;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .side-menu ul li i {
            margin-right: 15px;
            min-width: 20px;
        }

        .side-menu ul li span {
            display: inline-block;
            transition: opacity 0.3s ease;
        }

        .side-menu.collapsed ul li span,
        .side-menu.collapsed .brand-name h1 {
            display: none;
        }

        .side-menu ul li:hover,
        .side-menu ul li.active:not(:first-child),
        .side-menu ul li:first-child:hover {
            background-color: #fff;
            color: var(--primary);
            padding-left: 20px;
        }

        .brand-name {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .brand-name i {
            font-size: 32px;
            color: #fff;
        }

        .brand-name h1 {
            color: white;
            font-size: 24px;
            transition: opacity 0.3s;
        }

        .container {
            margin-left: 250px;
            transition: margin-left 0.3s;
        }

        .container.collapsed {
            margin-left: 70px;
        }

        .header {
            height: 10vh;
            display: flex;
            align-items: center;
            padding: 0 20px;
            background-color: var(--white);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .nav {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .dashboard1 h1 {
            cursor: pointer;
            font-size: 34px;
            color: black;
            padding-left: 40px;
        }

        .search {
            position: absolute;
            left: 60%;
            transform: translateX(-50%);
            display: flex;
            align-items: center;
        }

        .search form {
            position: relative;
            display: flex;
            align-items: center;
        }

        .search input {
            padding: 10px 40px 10px 10px;
            border-radius: 5px;
            border: 2px solid var(--primary);
            font-size: 16px;
            width: 250px;
            background: var(--search-bg);
        }

        .search input:focus {
            outline: none;
            border-color: #d43f4d;
            box-shadow: 0 0 5px rgba(240, 84, 98, 0.3);
        }

        .search button.search-btn {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background: var(--primary);
            border: none;
            color: #fff;
            font-size: 18px;
            padding: 8px;
            border-radius: 3px;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s;
        }

        .search button.search-btn:hover {
            background: #d43f4d;
            transform: translateY(-50%) scale(1.1);
        }

        .search button.clear-btn {
            position: absolute;
            right: 40px;
            top: 50%;
            transform: translateY(-50%);
            background: #999;
            border: none;
            color: #fff;
            font-size: 18px;
            padding: 8px;
            border-radius: 3px;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s;
        }

        .search button.clear-btn:hover {
            background: #777;
            transform: translateY(-50%) scale(1.1);
        }

        .user {
            display: flex;
            align-items: center;
        }

        .user .btn {
            margin-right: 15px;
        }

        .user img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-left: 15px;
        }

        .notification {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            font-size: 20px;
            color: var(--primary);
            margin: 0 10px;
        }

        .notification::after {
            content: "6";
            position: absolute;
            top: -5px;
            right: -5px;
            background: red;
            color: white;
            width: 18px;
            height: 18px;
            font-size: 12px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .content {
            padding: 20px;
        }

        .cards {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .card {
            background: var(--white);
            flex: 1;
            min-width: 200px;
            height: 130px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .card i {
            font-size: 40px;
            color: var(--primary);
        }

        .content-2 {
            margin-top: 30px;
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }

        .recent-payments, .add-student-form {
            flex: 1;
            background: var(--white);
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            min-width: 300px;
        }

        .recent-payments {
            flex-basis: 65%;
        }

        .title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #999;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th, table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            white-space: nowrap;
        }

        table tr:hover {
            background-color: #f9f9f9;
        }

        table tr img {
            width: 35px;
            height: 35px;
            border-radius: 50%;
        }

        table td:nth-child(3),
        table td:nth-child(4) {
            text-align: center;
        }

        .add-student-form form {
            display: flex;
            flex-direction: column;
            margin-bottom: 20px;
        }

        .add-student-form input {
            padding: 10px;
            margin-bottom: 10px;
            border: 2px solid var(--primary);
            border-radius: 5px;
            font-size: 16px;
        }

        .add-student-form button {
            background: var(--primary);
            color: white;
            padding: 10px;
            border-radius: 5px;
            font-size: 16px;
            border: none;
            cursor: pointer;
            transition: 0.3s;
        }

        .add-student-form button:hover {
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        @media screen and (max-width: 768px) {
            .side-menu {
                width: 70px;
            }

            .container {
                margin-left: 70px;
            }

            .cards {
                flex-direction: column;
            }

            .search {
                display: none;
            }

            .content-2 {
                flex-direction: column;
            }

            .recent-payments {
                width: 100%;
            }
        }
    </style>
</head>
<body>

  <div class="side-menu" id="sidebar">
    <div class="brand-name">
      <i class="fas fa-graduation-cap"></i>
      <h1>DOLLAR</h1>
    </div>
    <ul>
      <li><i class="fas fa-home"></i><span>Home</span></li>
      <li><i class="fas fa-user-graduate"></i><span>Students</span></li>
      <li><i class="fas fa-chalkboard-teacher"></i><span>Teachers</span></li>
      <li><i class="fas fa-school"></i><span>Schools</span></li>
      <li><i class="fas fa-piggy-bank"></i><span>Income</span></li>
      <li><i class="fas fa-question-circle"></i><span>Help</span></li>
      <li><i class="fas fa-cog"></i><span>Settings</span></li>
    </ul>
  </div>

  <div class="container" id="main-container">
    <div class="header">
      <div class="nav">
        <div class="dashboard1">
          <h1 onclick="toggleSidebar()">☰ Dashboard</h1>
        </div>
        <div class="search">
          <form id="searchForm" method="GET">
            <input type="text" name="search" id="searchInput" placeholder="Search by name or school..." value="<?php echo htmlspecialchars($search_query); ?>">
            <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
            <?php if ($search_query): ?>
              <button type="button" class="clear-btn" onclick="window.location.href='index.php'"><i class="fas fa-times"></i></button>
            <?php endif; ?>
          </form>
        </div>
        <div class="user">
          <div class="notification"><i class="fas fa-bell"></i></div>
          <img src="mb.jpg" alt="User">
        </div>
      </div>
    </div>

    <div class="content">
      <div class="cards">
        <div class="card">
          <div>
            <h1>2194</h1>
            <h3>Students</h3>
          </div>
          <i class="fas fa-user-graduate"></i>
        </div>
        <div class="card">
          <div>
            <h1>53</h1>
            <h3>Teachers</h3>
          </div>
          <i class="fas fa-chalkboard-teacher"></i>
        </div>
        <div class="card">
          <div>
            <h1>5</h1>
            <h3>Schools</h3>
          </div>
          <i class="fas fa-school"></i>
        </div>
        <div class="card">
          <div>
            <h1>350000</h1>
            <h3>Income</h3>
          </div>
          <i class="fas fa-hand-holding-usd"></i>
        </div>
      </div>

      <div class="content-2">
        <div class="add-student-form">
          <div class="title">
            <h2>Add New Student</h2>
          </div>
          <form method="POST" onsubmit="return validateForm()">
            <input type="text" id="name" name="name" placeholder="Name" required />
            <input type="text" id="school" name="school" placeholder="School" required />
            <input type="text" id="amount" name="amount" placeholder="Amount" required />
            <button type="submit" class="submit-button">Add</button>
          </form>
        </div>

        <div class="recent-payments">
          <div class="title">
            <h2>Recent Payments</h2>
            <a href="#" class="btn">View All</a>
          </div>
          <table>
            <tr>
              <th>Name</th>
              <th>School</th>
              <th>Amount</th>
              <th>Options</th>
            </tr>
            <?php
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['school']) . "</td>";
                    echo "<td>$" . number_format($row['amount'], 2) . "</td>";
                    echo "<td>";
                    echo "<a href='#' class='btn'>View</a>";
                    echo "<form method='POST' style='display:inline;' onsubmit='return confirm(\"Are you sure you want to delete this student?\");'>";
                    echo "<input type='hidden' name='delete_id' value='" . $row['id'] . "'>";
                    echo "<button type='submit' class='btn btn-danger'>Delete</button>";
                    echo "</form>";
                    echo "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='4'>" . ($search_query ? "No students found matching your search." : "No recent payments.") . "</td></tr>";
            }
            ?>
          </table>
        </div>
      </div>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    function toggleSidebar() {
      const sidebar = document.getElementById('sidebar');
      const container = document.getElementById('main-container');
      sidebar.classList.toggle('collapsed');
      container.classList.toggle('collapsed');
    }

    function validateForm() {
      const name = document.getElementById("name").value.trim();
      const school = document.getElementById("school").value.trim();
      const amount = document.getElementById("amount").value.trim();

      if (!name || !school || !amount) {
        alert("Please fill in all fields.");
        return false;
      }
      if (!/^\d+(\.\d{1,2})?$/.test(amount)) {
        alert("Please enter a valid amount (e.g., 100.00).");
        return false;
      }
      return true;
    }

    $(document).ready(function() {
      $('#searchForm').on('submit', function(e) {
        e.preventDefault();
        const searchValue = $('#searchInput').val().trim();
        window.location.href = '?search=' + encodeURIComponent(searchValue);
      });
    });
  </script>
</body>
</html>

<?php $conn->close(); ?>