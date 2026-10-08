<?php

declare(strict_types=1);

/*
 * print.php
 *
 * Receives print jobs from the web page and sends them
 * to Home Assistant through the REST API.
 */


/* =========================================================
 * CONFIGURATION
 * ========================================================= */

$HA_URL = 'https://< HA URL >';

$HA_TOKEN = '< LONG LIVED TOKEM >';


/* =========================================================
 * CORS
 * ========================================================= */

header(
    'Access-Control-Allow-Origin: https://< YOUR PAGE URL >'
);

header(
    'Access-Control-Allow-Headers: Content-Type'
);

header(
    'Access-Control-Allow-Methods: POST, OPTIONS'
);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}


/* =========================================================
 * RESPONSE
 * ========================================================= */

header('Content-Type: application/json; charset=utf-8');


function jsonResponse(
    bool $success,
    array $data = [],
    int $status = 200
): never {
    http_response_code($status);

    echo json_encode(
        array_merge(
            [
                'success' => $success
            ],
            $data
        ),
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


/* =========================================================
 * TEXT CLEANING
 * ========================================================= */

function cleanText(string $text): string
{
    return preg_replace(
        '/[^\P{C}\n\r\t]/u',
        '',
        $text
    ) ?? '';
}


/* =========================================================
 * CLIENT IP
 * ========================================================= */

function getClientIp(): string
{
    $realIp = $_SERVER['HTTP_X_REAL_IP'] ?? '';

    if (
        $realIp !== '' &&
        filter_var($realIp, FILTER_VALIDATE_IP)
    ) {
        return $realIp;
    }

    $remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';

    if (
        $remoteIp !== '' &&
        filter_var($remoteIp, FILTER_VALIDATE_IP)
    ) {
        return $remoteIp;
    }

    return 'unknown';
}


/* =========================================================
 * HOME ASSISTANT API
 * ========================================================= */

function callHomeAssistant(
    string $url,
    string $token,
    array $payload
): array {

    $json = json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    if ($json === false) {
        throw new RuntimeException(
            'Could not encode Home Assistant request.'
        );
    }

    $ch = curl_init($url);

    if ($ch === false) {
        throw new RuntimeException(
            'Could not initialize cURL.'
        );
    }

    curl_setopt_array(
        $ch,
        [
            CURLOPT_POST => true,

            CURLOPT_POSTFIELDS => $json,

            CURLOPT_RETURNTRANSFER => true,

            CURLOPT_CONNECTTIMEOUT => 10,

            CURLOPT_TIMEOUT => 30,

            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
                'Accept: application/json'
            ]
        ]
    );

    $response = curl_exec($ch);

    if ($response === false) {

        $error = curl_error($ch);

        curl_close($ch);

        throw new RuntimeException(
            'Home Assistant connection failed: ' . $error
        );
    }

    $httpCode = curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );

    curl_close($ch);

    if ($httpCode < 200 || $httpCode >= 300) {

        throw new RuntimeException(
            'Home Assistant returned HTTP ' .
            $httpCode .
            '.'
        );
    }

    return [
        'http_code' => $httpCode,
        'response' => $response
    ];
}


/* =========================================================
 * REQUEST VALIDATION
 * ========================================================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    jsonResponse(
        false,
        [
            'error' => 'Only POST requests are allowed.'
        ],
        405
    );
}


/*
 * Maximum request size: 15 MB.
 */
$contentLength = (int) (
    $_SERVER['CONTENT_LENGTH'] ?? 0
);

if ($contentLength > 15 * 1024 * 1024) {

    jsonResponse(
        false,
        [
            'error' => 'Request is too large.'
        ],
        413
    );
}


/* =========================================================
 * READ JSON
 * ========================================================= */

$rawInput = file_get_contents('php://input');

if ($rawInput === false || $rawInput === '') {

    jsonResponse(
        false,
        [
            'error' => 'Empty request.'
        ],
        400
    );
}


$data = json_decode(
    $rawInput,
    true
);


if (!is_array($data)) {

    jsonResponse(
        false,
        [
            'error' => 'Invalid JSON.'
        ],
        400
    );
}


/* =========================================================
 * JOB TYPE
 * ========================================================= */

$type = $data['type'] ?? '';

if (!is_string($type)) {
    $type = '';
}


if (!in_array(
    $type,
    [
        'text',
        'image'
    ],
    true
)) {

    jsonResponse(
        false,
        [
            'error' => 'Invalid print type.'
        ],
        400
    );
}


/* =========================================================
 * TICKET INFORMATION
 * ========================================================= */

/*
 * Unix timestamp.
 *
 * This is generated server-side.
 */
$ticket = time();


/*
 * Amsterdam local date/time.
 */
$dateTime = new DateTimeImmutable(
    'now',
    new DateTimeZone('Europe/Amsterdam')
);


$formattedDateTime = $dateTime->format(
    'Y-m-d H:i:s'
);


/*
 * Client IP.
 */
$clientIp = getClientIp();


/* =========================================================
 * TEXT PRINT
 * ========================================================= */

if ($type === 'text') {

    $text = $data['text'] ?? '';

    if (!is_string($text)) {

        jsonResponse(
            false,
            [
                'error' => 'Invalid text.'
            ],
            400
        );
    }


    /*
     * Remove whitespace at beginning/end.
     */
    $text = trim($text);


    /*
     * Do not allow an empty message.
     */
    if ($text === '') {

        jsonResponse(
            false,
            [
                'error' => 'Message cannot be empty.'
            ],
            400
        );
    }


    /*
     * Maximum 1024 UTF-8 characters.
     */
    if (mb_strlen($text, 'UTF-8') > 1024) {

        jsonResponse(
            false,
            [
                'error' =>
                    'Message is limited to 1024 characters.'
            ],
            400
        );
    }


    /*
     * Remove unwanted control characters.
     *
     * Newline, carriage return and tab remain allowed.
     */
    $text = cleanText($text);


    /*
     * Home Assistant script:
     *
     * script.escpos_print_text
     */
    $haEndpoint =
        $HA_URL .
        '/api/services/script/escpos_print_text';


    $payload = [
        'ticket' => (string) $ticket,

        'datetime' => $formattedDateTime,

        'ip' => $clientIp,

        'text' => $text
    ];


    try {

        callHomeAssistant(
            $haEndpoint,
            $HA_TOKEN,
            $payload
        );

    } catch (Throwable $e) {

        error_log(
            'ESC/POS text print failed: ' .
            $e->getMessage()
        );

        jsonResponse(
            false,
            [
                'error' =>
                    'Could not send the print job.'
            ],
            502
        );
    }


    /*
     * Successful print.
     */
    jsonResponse(
        true,
        [
            'ticket' => $ticket
        ]
    );
}


/* =========================================================
 * IMAGE PRINT
 * ========================================================= */

if ($type === 'image') {

    $image = $data['image'] ?? '';

    if (!is_string($image)) {
        jsonResponse(
            false,
            [
                'error' => 'Invalid image data.'
            ],
            400
        );
    }


    /*
     * Accept an image data URI.
     */
    if (
        !preg_match(
            '#^data:image/(png|jpeg|jpg|gif|bmp|tiff|webp);base64,(.+)$#is',
            $image,
            $matches
        )
    ) {
        jsonResponse(
            false,
            [
                'error' => 'Invalid image format.'
            ],
            400
        );
    }


    /*
     * Decode base64.
     */
    $binaryImage = base64_decode(
        $matches[2],
        true
    );

    if ($binaryImage === false) {
        jsonResponse(
            false,
            [
                'error' => 'Invalid base64 image.'
            ],
            400
        );
    }


    /*
     * Protect the PHP server from enormous uploads.
     */
    if (strlen($binaryImage) > 10 * 1024 * 1024) {
        jsonResponse(
            false,
            [
                'error' => 'Image is limited to 10 MB.'
            ],
            413
        );
    }


    /*
     * Verify that this is actually an image.
     */
    $imageInfo = @getimagesizefromstring(
        $binaryImage
    );

    if ($imageInfo === false) {
        jsonResponse(
            false,
            [
                'error' => 'Uploaded data is not a valid image.'
            ],
            400
        );
    }


    /*
     * Create GD image.
     */
    $source = @imagecreatefromstring(
        $binaryImage
    );

    if ($source === false) {
        jsonResponse(
            false,
            [
                'error' => 'Could not process image.'
            ],
            400
        );
    }


    /* =====================================================
     * EXIF ORIENTATION
     * ===================================================== */

    /*
     * Correct JPEG camera orientation.
     */
    if (
        function_exists('exif_read_data') &&
        isset($imageInfo['mime']) &&
        $imageInfo['mime'] === 'image/jpeg'
    ) {

        /*
         * Write temporary JPEG because exif_read_data()
         * works with a filename.
         */
        $tmpFile = tempnam(
            sys_get_temp_dir(),
            'print_'
        );

        if ($tmpFile !== false) {

            file_put_contents(
                $tmpFile,
                $binaryImage
            );

            $exif = @exif_read_data(
                $tmpFile
            );

            unlink($tmpFile);

            $orientation =
                $exif['Orientation'] ?? 1;

            switch ($orientation) {

                case 2:
                    imageflip(
                        $source,
                        IMG_FLIP_HORIZONTAL
                    );
                    break;

                case 3:
                    $source = imagerotate(
                        $source,
                        180,
                        0
                    );
                    break;

                case 4:
                    imageflip(
                        $source,
                        IMG_FLIP_VERTICAL
                    );
                    break;

                case 5:
                    imageflip(
                        $source,
                        IMG_FLIP_HORIZONTAL
                    );

                    $source = imagerotate(
                        $source,
                        90,
                        0
                    );
                    break;

                case 6:
                    $source = imagerotate(
                        $source,
                        -90,
                        0
                    );
                    break;

                case 7:
                    imageflip(
                        $source,
                        IMG_FLIP_HORIZONTAL
                    );

                    $source = imagerotate(
                        $source,
                        -90,
                        0
                    );
                    break;

                case 8:
                    $source = imagerotate(
                        $source,
                        90,
                        0
                    );
                    break;
            }
        }
    }


    /* =====================================================
     * RESIZE TO 80 MM PRINTER WIDTH
     * ===================================================== */

    $sourceWidth = imagesx($source);
    $sourceHeight = imagesy($source);

    $maxWidth = 576;


    if ($sourceWidth > $maxWidth) {

        $newWidth = $maxWidth;

        $newHeight = (int) round(
            $sourceHeight *
            ($newWidth / $sourceWidth)
        );

    } else {

        $newWidth = $sourceWidth;
        $newHeight = $sourceHeight;
    }


    /*
     * Create resized image.
     */
    $output = imagecreatetruecolor(
        $newWidth,
        $newHeight
    );


    /*
     * White background.
     *
     * Thermal printers do not have an alpha channel.
     */
    $white = imagecolorallocate(
        $output,
        255,
        255,
        255
    );

    imagefill(
        $output,
        0,
        0,
        $white
    );


    /*
     * Preserve aspect ratio.
     */
    imagecopyresampled(
        $output,
        $source,
        0,
        0,
        0,
        0,
        $newWidth,
        $newHeight,
        $sourceWidth,
        $sourceHeight
    );


    imagedestroy($source);


    /* =====================================================
     * COMPRESS IMAGE
     * ===================================================== */

    /*
     * Home Assistant has a 262144 character template
     * limit.
     *
     * We therefore keep the final base64 data comfortably
     * below that limit.
     *
     * Target:
     * approximately 180 KB binary
     * approximately 240 KB base64
     */
    $maxBase64Length = 240000;

    $jpegData = null;


    /*
     * Try progressively lower JPEG quality.
     */
    for ($quality = 80; $quality >= 30; $quality -= 5) {

        ob_start();

        imagejpeg(
            $output,
            null,
            $quality
        );

        $candidate = ob_get_clean();

        if ($candidate === false) {
            continue;
        }

        $candidateBase64 =
            base64_encode($candidate);


        if (
            strlen($candidateBase64) <=
            $maxBase64Length
        ) {

            $jpegData = $candidateBase64;

            break;
        }
    }


    /*
     * If quality reduction was not enough, reduce the
     * image dimensions as well.
     */
    if ($jpegData === null) {

        $currentWidth = $newWidth;
        $currentHeight = $newHeight;


        while (
            $jpegData === null &&
            $currentWidth > 200
        ) {

            $currentWidth = (int) round(
                $currentWidth * 0.85
            );

            $currentHeight = (int) round(
                $currentHeight * 0.85
            );


            $smaller = imagecreatetruecolor(
                $currentWidth,
                $currentHeight
            );


            $white = imagecolorallocate(
                $smaller,
                255,
                255,
                255
            );

            imagefill(
                $smaller,
                0,
                0,
                $white
            );


            imagecopyresampled(
                $smaller,
                $output,
                0,
                0,
                0,
                0,
                $currentWidth,
                $currentHeight,
                $newWidth,
                $newHeight
            );


            for (
                $quality = 70;
                $quality >= 30;
                $quality -= 5
            ) {

                ob_start();

                imagejpeg(
                    $smaller,
                    null,
                    $quality
                );

                $candidate =
                    ob_get_clean();

                if ($candidate === false) {
                    continue;
                }

                $candidateBase64 =
                    base64_encode($candidate);


                if (
                    strlen($candidateBase64) <=
                    $maxBase64Length
                ) {

                    $jpegData =
                        $candidateBase64;

                    break;
                }
            }


            imagedestroy($smaller);
        }
    }


    imagedestroy($output);


    /*
     * We could not make the image small enough.
     */
    if ($jpegData === null) {

        jsonResponse(
            false,
            [
                'error' =>
                    'Image could not be compressed enough for printing.'
            ],
            413
        );
    }


    /*
     * Create final JPEG data URI.
     */
    $normalizedImage =
        'data:image/jpeg;base64,' .
        $jpegData;


    /* =====================================================
     * HOME ASSISTANT
     * ===================================================== */

    $haEndpoint =
        $HA_URL .
        '/api/services/script/escpos_print_image';


    $payload = [
        'ticket' => (string) $ticket,

        'datetime' => $formattedDateTime,

        'ip' => $clientIp,

        'image' => $normalizedImage
    ];


    try {

        callHomeAssistant(
            $haEndpoint,
            $HA_TOKEN,
            $payload
        );

    } catch (Throwable $e) {

        error_log(
            'ESC/POS image print failed: ' .
            $e->getMessage()
        );

        jsonResponse(
            false,
            [
                'error' =>
                    'Could not send the print job.'
            ],
            502
        );
    }


    /*
     * Successful print.
     */
    jsonResponse(
        true,
        [
            'ticket' => $ticket
        ]
    );
}

/* =========================================================
 * FALLBACK
 * ========================================================= */

jsonResponse(
    false,
    [
        'error' => 'Unsupported print job.'
    ],
    400
);
