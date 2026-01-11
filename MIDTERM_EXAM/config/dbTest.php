<?php
require_once "Database.php";

$db = new Database();
$conn = $db->getConnection();

  if ($conn) {
    echo "✅ Database Connected!";
  } else {
    echo "❌ Failed to connect!";
  }
?>
