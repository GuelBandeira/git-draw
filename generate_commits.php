<?php
session_start();

// Increase execution time and enable real-time output
set_time_limit(1800); // 30 minutes
ini_set('memory_limit', '-1');
ini_set('output_buffering', 0);
ini_set('implicit_flush', 1);
ob_implicit_flush(1);

// Keep connection alive
ignore_user_abort(true);

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');
header('Pragma: no-cache');

// GitHub OAuth configuration
define('GITHUB_API_URL', 'https://api.github.com');

$github_token = $_SESSION['github_token'] ?? null;
$github_user = $_SESSION['github_user'] ?? null;

if (!$github_token) {
    echo "data: " . json_encode(['success' => false, 'message' => 'Não autenticado. Por favor, conecte-se ao GitHub.']) . "\n\n";
    flush();
    exit;
}

// Get user information for commits
$user_name = $github_user['name'] ?? $github_user['login'] ?? 'GitHub User';
$user_email = $github_user['email'] ?? ($github_user['login'] . '@users.noreply.github.com');

// Get input data
$message = '';
$repo = '';
$year = date('Y');
$gridData = [];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['message'])) {
    $message = $_GET['message'] ?? '';
    $repo = $_GET['repo'] ?? '';
    $year = $_GET['year'] ?? date('Y');
    $gridData = isset($_GET['gridData']) ? json_decode($_GET['gridData'], true) : [];
} else {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        echo "data: " . json_encode(['success' => false, 'message' => 'Dados inválidos']) . "\n\n";
        flush();
        exit;
    }
    $message = $input['message'] ?? '';
    $repo = $input['repo'] ?? '';
    $year = $input['year'] ?? date('Y');
    $gridData = $input['gridData'] ?? [];
}

if (empty($message) || empty($repo) || empty($gridData)) {
    echo "data: " . json_encode(['success' => false, 'message' => 'Parâmetros inválidos']) . "\n\n";
    flush();
    exit;
}

// Parse repository name
$repo_parts = explode('/', $repo);
if (count($repo_parts) !== 2) {
    echo "data: " . json_encode(['success' => false, 'message' => 'Formato de repositório inválido. Use: usuario/repositorio']) . "\n\n";
    flush();
    exit;
}

$owner = $repo_parts[0];
$repo_name = $repo_parts[1];

// Validate year
$year = intval($year);
if ($year < 2021 || $year > date('Y')) {
    echo "data: " . json_encode(['success' => false, 'message' => 'Ano inválido.']) . "\n\n";
    flush();
    exit;
}

// Calculate dates
$start_date = new DateTime();
$start_date->setDate($year, 12, 31);
$start_date->setTime(12, 0, 0);

$day_of_week = (int)$start_date->format('w');
if ($day_of_week > 0) {
    $start_date->modify('+' . (7 - $day_of_week) . ' days');
}
$start_date->modify('-51 weeks');

$commits_created = 0;
$errors = [];
$commit_tasks = [];

// --- OTIMIZAÇÃO 1 + INTENSIDADE ---
// Agora respeita o nível vindo do front-end (1 a 4)
for ($week = 0; $week < 52; $week++) {
    for ($day = 0; $day < 7; $day++) {
        $level = intval($gridData[$week][$day] ?? 0);

        if ($level > 0) {
            // Data base do dia
            $base_date = clone $start_date;
            $base_date->modify('+' . ($week * 7 + $day) . ' days');

            // Loop baseado na intensidade (level)
            // Se level for 4, cria 4 tarefas de commit para este dia
            for ($i = 0; $i < $level; $i++) {
                $commit_date = clone $base_date;
                // Adiciona alguns minutos para cada commit no mesmo dia ter horário diferente
                // Isso ajuda na ordenação do git log
                $commit_date->modify("+{$i} minutes");

                $commit_tasks[] = [
                    'date' => $commit_date,
                    'message' => $message,
                    'week' => $week,
                    'day' => $day
                ];
            }
        }
    }
}

// Sort by date ensures commits happen in chronological order
usort($commit_tasks, function ($a, $b) {
    return $a['date'] <=> $b['date'];
});

// Initialize repository if empty
$branch_info = getBranchInfo($github_token, $owner, $repo_name);
if (!$branch_info['success']) {
    echo "data: " . json_encode(['type' => 'progress', 'message' => 'Inicializando repositório...', 'current' => 0, 'total' => count($commit_tasks), 'percentage' => 0]) . "\n\n";
    flush();

    $init_result = initializeRepository($github_token, $owner, $repo_name);
    if (!$init_result['success']) {
        echo "data: " . json_encode(['type' => 'error', 'success' => false, 'message' => 'Falha ao inicializar: ' . $init_result['message']]) . "\n\n";
        exit;
    }
    // Refresh branch info after init
    $branch_info = getBranchInfo($github_token, $owner, $repo_name);
}

// --- OTIMIZAÇÃO 2: Processamento em Cadeia (Chaining) ---
$last_commit_sha = $branch_info['sha'];
$last_tree_sha = $branch_info['tree_sha'];

$total_tasks = count($commit_tasks);
$processed = 0;

if ($total_tasks === 0) {
    echo "data: " . json_encode(['type' => 'complete', 'success' => true, 'message' => 'Nenhum dia selecionado.', 'commits' => 0]) . "\n\n";
    exit;
}

foreach ($commit_tasks as $task) {
    // 1. Criar Árvore (Tree)
    $unique_id = uniqid();
    $timestamp = $task['date']->format('Y-m-d H:i:s');
    $file_content = "Commit date: {$timestamp}\nMessage: {$task['message']}\nID: {$unique_id}";

    $tree_result = createGitTree($github_token, $owner, $repo_name, $last_tree_sha, 'commits.txt', $file_content);

    if (!$tree_result['success']) {
        $errors[] = "Week {$task['week']}, Day {$task['day']} (Tree): " . $tree_result['message'];
        // Se der erro crítico consecutivamente, para
        if (count($errors) > 5) break;
        continue;
    }

    $new_tree_sha = $tree_result['sha'];

    // 2. Criar Objeto de Commit
    $commit_result = createGitCommitObject($github_token, $owner, $repo_name, $last_commit_sha, $new_tree_sha, $task['date'], $task['message'], $user_name, $user_email);

    if (!$commit_result['success']) {
        $errors[] = "Week {$task['week']}, Day {$task['day']} (Commit): " . $commit_result['message'];
        if (count($errors) > 5) break;
        continue;
    }

    // Atualiza ponteiros
    $last_commit_sha = $commit_result['sha'];
    $last_tree_sha = $new_tree_sha;

    $commits_created++;
    $processed++;

    // Progress update
    $percentage = round(($processed / $total_tasks) * 100);
    echo "data: " . json_encode([
        'type' => 'progress',
        'current' => $processed,
        'total' => $total_tasks,
        'percentage' => $percentage,
        'commits_created' => $commits_created,
        'message' => "Processando commits... {$commits_created}/{$total_tasks}"
    ]) . "\n\n";

    if (ob_get_level() > 0) ob_flush();
    flush();

    // Keep-alive ping
    if ($processed % 20 === 0) {
        echo ": keep-alive\n\n";
        flush();
    }
    // Delay reduzido para 5ms pois agora temos muito mais volume de commits
    usleep(5000);
}

// --- OTIMIZAÇÃO 3: Atualização Final da Branch ---
if ($commits_created > 0) {
    echo "data: " . json_encode(['type' => 'progress', 'message' => 'Finalizando: Atualizando branch principal...', 'current' => $total_tasks, 'total' => $total_tasks, 'percentage' => 99]) . "\n\n";
    flush();

    $ref_result = updateGitRef($github_token, $owner, $repo_name, $last_commit_sha);

    if ($ref_result['success']) {
        echo "data: " . json_encode([
            'type' => 'complete',
            'success' => true,
            'message' => "Sucesso! {$commits_created} commits criados e enviados.",
            'commits' => $commits_created,
            'errors' => $errors
        ]) . "\n\n";
    } else {
        echo "data: " . json_encode([
            'type' => 'complete',
            'success' => false,
            'message' => "Commits gerados, mas falha ao atualizar a branch: " . $ref_result['message']
        ]) . "\n\n";
    }
} else {
    echo "data: " . json_encode([
        'type' => 'complete',
        'success' => false,
        'message' => 'Nenhum commit foi criado com sucesso.',
        'errors' => $errors
    ]) . "\n\n";
}
flush();

// --- FUNÇÕES HELPER ---

function createGitTree($token, $owner, $repo, $base_tree_sha, $path, $content)
{
    $data = [
        'base_tree' => $base_tree_sha,
        'tree' => [
            [
                'path' => $path,
                'mode' => '100644',
                'type' => 'blob',
                'content' => $content
            ]
        ]
    ];

    return makeGitHubRequest($token, "https://api.github.com/repos/{$owner}/{$repo}/git/trees", $data);
}

function createGitCommitObject($token, $owner, $repo, $parent_sha, $tree_sha, $date, $message, $name, $email)
{
    $commit_date = $date->format('c');
    $data = [
        'message' => $message,
        'tree' => $tree_sha,
        'parents' => [$parent_sha],
        'author' => ['name' => $name, 'email' => $email, 'date' => $commit_date],
        'committer' => ['name' => $name, 'email' => $email, 'date' => $commit_date]
    ];

    return makeGitHubRequest($token, "https://api.github.com/repos/{$owner}/{$repo}/git/commits", $data);
}

function updateGitRef($token, $owner, $repo, $new_sha)
{
    $data = ['sha' => $new_sha, 'force' => true];
    $res = makeGitHubRequest($token, "https://api.github.com/repos/{$owner}/{$repo}/git/refs/heads/main", $data, 'PATCH');
    if ($res['success']) return $res;
    return makeGitHubRequest($token, "https://api.github.com/repos/{$owner}/{$repo}/git/refs/heads/master", $data, 'PATCH');
}

function makeGitHubRequest($token, $url, $data, $method = 'POST')
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'User-Agent: GitHub-Art-Gen',
        'Accept: application/vnd.github+json',
        'X-GitHub-Api-Version: 2022-11-28',
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err = curl_error($ch);
    curl_close($ch);

    if ($curl_err) {
        return ['success' => false, 'message' => "Curl Error: $curl_err"];
    }

    $json = json_decode($response, true);

    if ($http_code === 201 || $http_code === 200) {
        return ['success' => true, 'sha' => $json['sha'] ?? null, 'tree_sha' => $json['tree']['sha'] ?? null, 'object' => $json['object'] ?? null];
    }

    return ['success' => false, 'message' => "HTTP $http_code: " . ($json['message'] ?? $response)];
}

function getBranchInfo($token, $owner, $repo)
{
    $branches = ['main', 'master'];
    foreach ($branches as $branch) {
        $ch = curl_init("https://api.github.com/repos/{$owner}/{$repo}/git/ref/heads/{$branch}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'User-Agent: GitHub-Art-Gen',
            'Accept: application/vnd.github+json',
            'X-GitHub-Api-Version: 2022-11-28'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200) {
            $data = json_decode($response, true);
            $commit_sha = $data['object']['sha'];
            $ch2 = curl_init("https://api.github.com/repos/{$owner}/{$repo}/git/commits/{$commit_sha}");
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch2, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $token,
                'User-Agent: GitHub-Art-Gen',
                'Accept: application/vnd.github+json',
                'X-GitHub-Api-Version: 2022-11-28'
            ]);
            curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
            $res2 = curl_exec($ch2);
            $data2 = json_decode($res2, true);
            curl_close($ch2);
            return ['success' => true, 'sha' => $commit_sha, 'tree_sha' => $data2['tree']['sha']];
        }
    }
    return ['success' => false];
}

function initializeRepository($token, $owner, $repo)
{
    $content = base64_encode("# GitHub Art\nGenerated automatically.");
    $data = ['message' => 'Init', 'content' => $content];
    $ch = curl_init("https://api.github.com/repos/{$owner}/{$repo}/contents/README.md");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'User-Agent: GitHub-Art-Gen',
        'Accept: application/vnd.github+json',
        'X-GitHub-Api-Version: 2022-11-28'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    sleep(1);
    return ['success' => $code === 201];
}
