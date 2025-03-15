<?php
session_start(); // Start the session

// Redirect to login if user is not logged in or not an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

include 'db.php'; // Include the database connection file
include 'send_email.php'; // Include the email function

// Handle ticket status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ticket_id'])) {
    $ticket_id = $_POST['ticket_id'];
    $status = $_POST['status'];

    // Update ticket status
    $stmt = $conn->prepare("UPDATE tickets SET status = ? WHERE id = ?");
    $stmt->execute([$status, $ticket_id]);

    // Fetch user email and ticket details
    $stmt = $conn->prepare("SELECT users.email, tickets.subject, tickets.status FROM tickets JOIN users ON tickets.user_id = users.id WHERE tickets.id = ?");
    $stmt->execute([$ticket_id]);
    $ticket_details = $stmt->fetch();

    // Send email notification to the user
    $to = $ticket_details['email']; // User's email
    $subject = "Ticket Status Updated";
    $message = "Hello,\n\nThe status of your ticket has been updated.\n\nTicket Details:\nSubject: " . $ticket_details['subject'] . "\nNew Status: " . $status . "\n\nThank you!";

    $result = sendEmail($to, $subject, $message);

    if ($result === true) {
        echo "<div class='alert alert-success'>Ticket status updated successfully. The user has been notified.</div>";
    } else {
        echo "<div class='alert alert-warning'>Ticket status updated successfully, but the email notification failed to send. Error: $result</div>";
    }
}

// Pagination settings
$tickets_per_page = 10; // Number of tickets per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page
$offset = ($page - 1) * $tickets_per_page; // Offset for SQL query

// Fetch all tickets with pagination
$stmt = $conn->prepare("SELECT tickets.*, users.username FROM tickets JOIN users ON tickets.user_id = users.id ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $tickets_per_page, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$tickets = $stmt->fetchAll();

// Fetch total number of tickets for pagination
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM tickets");
$stmt->execute();
$total_tickets = $stmt->fetch()['total'];
$total_pages = ceil($total_tickets / $tickets_per_page); // Total pages
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- Logout Button -->
    <div class="text-end mb-3">
        <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>

    <div class="container mt-5">
        <h2>Admin Dashboard</h2>
        <p>View and manage all tickets below.</p>

        <!-- Display All Tickets -->
        <h3>All Tickets</h3>
        <?php if (count($tickets) > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Subject</th>
                        <th>Category</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $ticket): ?>
                        <tr>
                            <td><?php echo $ticket['id']; ?></td>
                            <td><?php echo $ticket['username']; ?></td>
                            <td><?php echo $ticket['subject']; ?></td>
                            <td><?php echo $ticket['category']; ?></td>
                            <td><?php echo $ticket['priority']; ?></td>
                            <td><?php echo $ticket['status']; ?></td>
                            <td><?php echo $ticket['created_at']; ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                    <select name="status" onchange="this.form.submit()">
                                        <option value="open" <?php echo $ticket['status'] == 'open' ? 'selected' : ''; ?>>Open</option>
                                        <option value="in_progress" <?php echo $ticket['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="resolved" <?php echo $ticket['status'] == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                    </select>
                                </form>
                            </td>
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