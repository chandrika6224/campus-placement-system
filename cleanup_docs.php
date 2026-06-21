<?php
require_once 'includes/config.php';
$conn->query("DELETE d1 FROM documents d1 INNER JOIN documents d2 WHERE d1.id > d2.id AND d1.user_id = d2.user_id AND d1.doc_name = d2.doc_name AND d1.file_size = d2.file_size AND ABS(TIMESTAMPDIFF(SECOND, d1.uploaded_at, d2.uploaded_at)) < 60");
echo "Done. Affected rows: " . $conn->affected_rows;
