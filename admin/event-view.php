<?php
// Redirect event-view to event-edit for now
// This provides a quick fix for the 404 error
$event_id = $_GET['id'] ?? 0;
header("Location: event-edit.php?id=$event_id");
exit;
