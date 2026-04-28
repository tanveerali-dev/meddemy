<?php
session_start();
include("../includes/db.php");

if (!isset($_SESSION['student_id'])) {
    header("Location: ../login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

$sql = "SELECT course.* FROM course 
        INNER JOIN enrollment ON course.course_id = enrollment.course_id 
        WHERE enrollment.student_id = '$student_id'";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Courses - MEDDEMY</title>
</head>
<body>
    <h2>My Courses</h2>
    <ul>
        <?php while($row = $result->fetch_assoc()) { ?>
            <li>
                <a href="view_course.php?id=<?php echo $row['course_id']; ?>">
                    <?php echo $row['title']; ?>
                </a>
            </li>
        <?php } ?>
    </ul>
    <a href="dashboard.php">Back to Dashboard</a>
</body>
</html>
