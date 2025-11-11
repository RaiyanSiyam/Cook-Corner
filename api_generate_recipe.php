<?php
// api_generate_recipe.php
// Upgraded backend to handle advanced recipe generation parameters and generate multiple options.

session_start();
header('Content-Type: application/json');

// --- Check for cURL extension ---
if (!function_exists('curl_init')) {
    http_response_code(500);
    echo json_encode(['error' => 'The cURL extension for PHP is not enabled on your server.']);
    exit;
}

// --- Get and Validate Input from the advanced form ---
$data = json_decode(file_get_contents('php://input'), true);

$ingredients = trim($data['ingredients'] ?? '');
if (empty($ingredients)) {
    http_response_code(400);
    echo json_encode(['error' => 'Ingredients list cannot be empty.']);
    exit;
}

// --- Build a detailed user prompt from all parameters ---
$user_prompt = "Generate 1 unique recipe options with the following constraints:\n";
$user_prompt .= "- Must use these core ingredients: {$ingredients}\n";

if (!empty($data['cuisine'])) {
    $user_prompt .= "- Cuisine style should be: {$data['cuisine']}\n";
}
if (!empty($data['diet']) && $data['diet'] !== 'None') {
    $user_prompt .= "- It must be a {$data['diet']} recipe\n";
}
if (!empty($data['meal_type']) && $data['meal_type'] !== 'Any') {
    $user_prompt .= "- This is for: {$data['meal_type']}\n";
}
if (!empty($data['cook_time']) && $data['cook_time'] !== 'Any') {
    $user_prompt .= "- Maximum total cooking time should be {$data['cook_time']} minutes\n";
}
if (!empty($data['servings']) && is_numeric($data['servings'])) {
    $user_prompt .= "- The recipe should make {$data['servings']} servings\n";
}
if (!empty($data['calories']) && is_numeric($data['calories'])) {
    $user_prompt .= "- Aim for approximately {$data['calories']} calories per serving\n";
}
if (!empty($data['protein']) && is_numeric($data['protein'])) {
    $user_prompt .= "- Aim for approximately {$data['protein']}g of protein per serving\n";
}
if (!empty($data['fat']) && is_numeric($data['fat'])) {
    $user_prompt .= "- Aim for approximately {$data['fat']}g of fat per serving\n";
}


// --- System prompt to instruct the AI on the output format ---
$system_prompt = "You are a creative chef. You MUST respond with only a valid JSON object. This object must contain a single key called 'recipes'. The value of 'recipes' must be an array of exactly 3 unique recipe objects. Each object in the array must have three keys: 'title' (a string), 'ingredients' (an array of strings), and 'instructions' (an array of strings). Do not include any other text, formatting, or explanation before or after the JSON object.";

// --- Set up the Gemini API call ---
$api_key = "AIzaSyAHNLcLTjLcM_BCo4rISxg_woNqlPpjV4E"; // Remember to add your key here
$api_url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-preview-05-20:generateContent?key={$api_key}";

if ($api_key === "PASTE_YOUR_API_KEY_HERE" || empty($api_key)) {
    http_response_code(500);
    echo json_encode(['error' => 'API Key is missing. Please add your key to the api_generate_recipe.php file.']);
    exit;
}

$payload = [
    'contents' => [['parts' => [['text' => $user_prompt]]]],
    'systemInstruction' => ['parts' => [['text' => $system_prompt]]],
    'generationConfig' => ['responseMimeType' => 'application/json']
];

// --- cURL Request (with local server SSL fix) ---
$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// --- Process the API Response ---
if ($http_code !== 200 || $response === false) {
    http_response_code(500);
    error_log("Gemini API Error: HTTP Code {$http_code} - Response: {$response}");
    echo json_encode(['error' => 'Failed to get a response from the AI chef. Please try again.']);
    exit;
}

$result = json_decode($response, true);
// The AI returns a JSON string that itself contains a JSON object. We pass this string directly to the frontend.
$generated_text = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;

if (!$generated_text) {
    http_response_code(500);
    echo json_encode(['error' => 'The AI chef returned an empty plate. Please try different ingredients or fewer constraints.']);
    exit;
}

// Pass the raw JSON string from the AI to the frontend JavaScript, which will parse it.
echo $generated_text;
?>

