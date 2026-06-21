<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false,'output'=>'Unauthorized']);
    exit();
}

$code     = $_POST['code'] ?? '';
$language = $_POST['language'] ?? 'python';
$input    = $_POST['input'] ?? '';

if (empty(trim($code))) {
    echo json_encode(['success'=>false,'output'=>'No code provided.']);
    exit();
}

// Security: block dangerous functions
$blocked = ['exec','shell_exec','system','passthru','popen','proc_open','file_get_contents','file_put_contents','unlink','rmdir','mkdir','chmod','chown','curl','wget','nc ','netcat','rm -','sudo','su '];
$codeLower = strtolower($code);
foreach ($blocked as $b) {
    if (strpos($codeLower, strtolower($b)) !== false && $language === 'php') {
        echo json_encode(['success'=>false,'output'=>"⚠️ Blocked: '$b' is not allowed."]);
        exit();
    }
}

$tmpDir  = sys_get_temp_dir();
$id      = uniqid('code_', true);
$output  = '';
$success = false;
$startTime = microtime(true);

try {
    switch ($language) {
        case 'python':
            $file = "$tmpDir/$id.py";
            file_put_contents($file, $code);
            if (!empty($input)) {
                $inputFile = "$tmpDir/{$id}_in.txt";
                file_put_contents($inputFile, $input);
                $cmd = "python \"$file\" < \"$inputFile\" 2>&1";
            } else {
                $cmd = "python \"$file\" 2>&1";
            }
            $output = shell_exec($cmd) ?? 'Execution failed.';
            @unlink($file);
            if (!empty($inputFile)) @unlink($inputFile);
            $success = true;
            break;

        case 'javascript':
            $file = "$tmpDir/$id.js";
            file_put_contents($file, $code);
            if (!empty($input)) {
                $inputFile = "$tmpDir/{$id}_in.txt";
                file_put_contents($inputFile, $input);
                $output = shell_exec("node \"$file\" < \"$inputFile\" 2>&1") ?? 'Execution failed or Node.js not available.';
                @unlink($inputFile);
            } else {
                $output = shell_exec("node \"$file\" 2>&1") ?? 'Execution failed or Node.js not available.';
            }
            @unlink($file);
            $success = true;
            break;

        case 'php':
            // Safe PHP execution via eval with output buffering
            ob_start();
            try {
                $safeCode = str_replace('<?php', '', str_replace('?>', '', $code));
                eval($safeCode);
            } catch (Throwable $e) {
                echo "Error: " . $e->getMessage();
            }
            $output = ob_get_clean();
            $success = true;
            break;

        case 'c':
        case 'cpp':
            $ext  = $language === 'c' ? 'c' : 'cpp';
            $file = "$tmpDir/$id.$ext";
            $bin  = "$tmpDir/$id";
            file_put_contents($file, $code);
            $compiler = $language === 'c' ? 'gcc' : 'g++';
            $compileOut = shell_exec("$compiler \"$file\" -o \"$bin\" 2>&1");
            if (!empty($compileOut)) {
                $output = "Compilation Error:\n$compileOut";
            } else {
                $inputFile = '';
                if (!empty($input)) {
                    $inputFile = "$tmpDir/{$id}_in.txt";
                    file_put_contents($inputFile, $input);
                    $output = shell_exec("\"$bin\" < \"$inputFile\" 2>&1") ?? 'Timed out.';
                } else {
                    $output = shell_exec("\"$bin\" 2>&1") ?? 'Timed out.';
                }
                if ($inputFile) @unlink($inputFile);
                @unlink($bin);
            }
            @unlink($file);
            $success = true;
            break;

        case 'java':
            $file = "$tmpDir/Main_$id.java";
            // Rename class to avoid conflicts
            $javaCode = preg_replace('/public\s+class\s+\w+/', "public class Main_$id", $code);
            file_put_contents($file, $javaCode);
            $compileOut = shell_exec("javac \"$file\" -d \"$tmpDir\" 2>&1");
            if (!empty($compileOut)) {
                $output = "Compilation Error:\n$compileOut";
            } else {
                $output = shell_exec("java -cp \"$tmpDir\" Main_$id 2>&1") ?? 'Timed out.';
                @unlink("$tmpDir/Main_$id.class");
            }
            @unlink($file);
            $success = true;
            break;

        default:
            $output = "Language '$language' not supported yet.";
    }
} catch (Exception $e) {
    $output = "Error: " . $e->getMessage();
}

$execTime = round((microtime(true) - $startTime) * 1000);
$output = $output ?: '(No output)';

echo json_encode([
    'success'   => $success,
    'output'    => htmlspecialchars($output),
    'exec_time' => $execTime,
]);
