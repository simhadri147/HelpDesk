<?php
session_start(); // Start the session

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db.php'; // Include the database connection file
include 'send_email.php'; // Include the email function

// Fetch user details
$stmt = $conn->prepare("SELECT email, username FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
$user_email = $user['email'];
$username = $user['username'];

// Handle ticket submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $subject = $_POST['subject'];
    $category = $_POST['category'];
    $priority = $_POST['priority'];
    $description = $_POST['description'];

    // Insert ticket into the database
    $stmt = $conn->prepare("INSERT INTO tickets (user_id, subject, category, priority, description) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $subject, $category, $priority, $description]);


    
    // Send email notification to the user
    $to = $user_email;
    $subject_email = "New Ticket Submitted";
    $message = "Hello " . $username . ",\n\nYour ticket has been submitted successfully.\n\nTicket Details:\nSubject: " . $subject . "\nCategory: " . $category . "\nPriority: " . $priority . "\nDescription: " . $description . "\n\nThank you!";

    $result = sendEmail($to, $subject_email, $message);

    if ($result === true) {
        echo "<div class='alert alert-success'>Ticket submitted successfully. You will receive a confirmation email shortly.</div>";
    } else {
        echo "<div class='alert alert-warning'>Ticket submitted successfully, but the email notification failed to send. Error: $result</div>";
    }

    // Notify admins about the new ticket
    $stmt = $conn->prepare("SELECT email FROM users WHERE role = 'admin'");
    $stmt->execute();
    $admins = $stmt->fetchAll();

    foreach ($admins as $admin) {
        $to = $admin['email']; // Admin's email
        $subject = "New Ticket Submitted";
        $message = "Hello Admin,\n\nA new ticket has been submitted.\n\nTicket Details:\nSubject: " . $subject . "\nCategory: " . $category . "\nPriority: " . $priority . "\nDescription: " . $description . "\n\nPlease review it in the admin dashboard.";

        $result = sendEmail($to, $subject, $message);

        // if ($result === true) {
        //     echo "<div class='alert alert-success'>Admin has been notified about the new ticket.</div>";
        // } else {
        //     echo "<div class='alert alert-warning'>Failed to notify admin about the new ticket. Error: $result</div>";
        // }
    }
}

// Pagination settings
$tickets_per_page = 10; // Number of tickets per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page
$offset = ($page - 1) * $tickets_per_page; // Offset for SQL query

// Fetch user's tickets with pagination
$stmt = $conn->prepare("SELECT * FROM tickets WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->bindValue(2, $tickets_per_page, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$tickets = $stmt->fetchAll();

// Fetch total number of tickets for pagination
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM tickets WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$total_tickets = $stmt->fetch()['total'];
$total_pages = ceil($total_tickets / $tickets_per_page); // Total pages
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- Logout Button -->
    <div class="text-end mb-3">
        <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>

    <div class="container mt-5">
        <h2>Welcome to Your Dashboard</h2>
        <p>Submit a new ticket or view your existing tickets below.</p>

        <!-- Ticket Submission Form -->
        <h3>Submit a New Ticket</h3>
        <form method="POST">
            <div class="mb-3">
                <label for="subject" class="form-label">Subject</label>
                <input type="text" class="form-control" id="subject" name="subject" required>
            </div>
            <div class="mb-3">
                <label for="category" class="form-label">Category</label>
                <input type="text" class="form-control" id="category" name="category" required>
            </div>
            <div class="mb-3">
                <label for="priority" class="form-label">Priority</label>
                <select class="form-control" id="priority" name="priority">
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Submit Ticket</button>
        </form>

        <!-- Display User's Tickets -->
        <h3 class="mt-5">Your Tickets</h3>
        <?php if (count($tickets) > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Subject</th>
                        <th>Category</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $ticket): ?>
                        <tr>
                            <td><?php echo $ticket['id']; ?></td>
                            <td><?php echo $ticket['subject']; ?></td>
                            <td><?php echo $ticket['category']; ?></td>
                            <td><?php echo $ticket['priority']; ?></td>
                            <td><?php echo $ticket['status']; ?></td>
                            <td><?php echo $ticket['created_at']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <nav aria-label="Page navigation">
                <ul class="pagination">
                    <?php if ($page > 1): ?>
                        <li class="page-item"><a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a></li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <li class="page-item"><a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php else: ?>
            <p>No tickets found.</p>
        <?php endif; ?>
    </div>
</body>
</html>