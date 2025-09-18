<?php

namespace SonPhoenix\VideoTools;

use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\X264;
use FFMpeg\Coordinate\TimeCode;

class VideoTools
{
    protected $ffmpeg;

    public function __construct()
    {
        $this->ffmpeg = FFMpeg::create();
    }

    /**
     * Convert a time string (hh:mm:ss, mm:ss, or ss) into seconds.
     *
     * @param string $time Time string (e.g. "1:20:15", "05:12", or "45")
     * @return int Number of seconds
     */
    protected function parseDuration(string $time): int
    {
        $parts = array_reverse(explode(':', $time));
        $seconds = 0;

        if (isset($parts[0])) {
            $seconds += (int)$parts[0]; // seconds
        }
        if (isset($parts[1])) {
            $seconds += (int)$parts[1] * 60; // minutes
        }
        if (isset($parts[2])) {
            $seconds += (int)$parts[2] * 3600; // hours
        }

        return $seconds;
    }

    /**
     * Trim a video between a start time and a duration.
     *
     * @param string $input    Path to input video
     * @param string $output   Path where trimmed video will be saved
     * @param string $start    Start time (e.g. "0:01:20")
     * @param string $duration Duration (e.g. "0:00:15")
     * @return bool True if successful, false otherwise
     */
    public function trim(string $input, string $output, string $start, string $duration)
    {
        $video = $this->ffmpeg->open($input);

        $format = new X264();

        $startSeconds = $this->parseDuration($start);
        $durationSeconds = $this->parseDuration($duration);

        $video->filters()->clip(
            TimeCode::fromSeconds($startSeconds),
            TimeCode::fromSeconds($durationSeconds)
        );

        $video->save($format, $output);

        return file_exists($output);
    }

    /**
     * Extract a thumbnail from a video at a given time.
     *
     * @param string $inputPath  Path to input video
     * @param string $outputPath Path where thumbnail image will be saved
     * @param string $time       Time position (e.g. "5", "00:00:10", "1:02:15")
     * @return string Path to saved thumbnail image
     */
    public function thumbnail(string $inputPath, string $outputPath, string $time = "1"): string
    {
        $video = $this->ffmpeg->open($inputPath);

        $seconds = $this->parseDuration($time);

        $frame = $video->frame(TimeCode::fromSeconds($seconds));
        $frame->save($outputPath);

        return $outputPath;
    }

    /**
     * Extract audio from a video file.
     *
     * @param string $input  Path to input video
     * @param string $output Path where extracted audio will be saved
     * @param string $format Audio format (mp3, wav, etc.)
     * @return bool True if successful, false otherwise
     */
    public function extractAudio(string $input, string $output, string $format = 'mp3'): bool
    {
        $video = $this->ffmpeg->open($input);

        // Use mp3 or wav, etc.
        $audioFormat = match ($format) {
            'mp3' => new \FFMpeg\Format\Audio\Mp3(),
            'wav' => new \FFMpeg\Format\Audio\Wav(),
            default => new \FFMpeg\Format\Audio\Mp3(),
        };

        $video->save($audioFormat, $output);

        return file_exists($output);
    }

    /**
     * Merge multiple videos into one, normalizing them to the same format first.
     *
     * @param array  $inputs Array of input video paths
     * @param string $output Path to save the merged video
     * @return bool True if successful, false otherwise
     */
    public function merge(array $inputs, string $output): bool
    {
        if (empty($inputs)) {
            return false;
        }

        // Single video - just copy
        if (count($inputs) === 1) {
            return copy($inputs[0], $output);
        }

        $normalizedFiles = [];
        $tempDir = sys_get_temp_dir();

        try {
            // Step 1: Normalize all videos to consistent format
            foreach ($inputs as $index => $input) {
                if (!file_exists($input)) {
                    throw new \InvalidArgumentException("Input file does not exist: $input");
                }

                $normalizedFile = $tempDir . '/normalized_' . uniqid() . '.mp4';

                // Normalize to consistent format: 30fps, same audio settings
                $normalizeCmd = sprintf(
                    'ffmpeg -y -i %s -c:v libx264 -r 30 -crf 23 -preset fast -c:a aac -b:a 128k -ar 44100 -ac 2 -avoid_negative_ts make_zero %s 2>&1',
                    escapeshellarg($input),
                    escapeshellarg($normalizedFile)
                );

                exec($normalizeCmd, $normalizeOutput, $normalizeReturn);

                if ($normalizeReturn === 0 && file_exists($normalizedFile)) {
                    $normalizedFiles[] = $normalizedFile;
                } else {
                    // Cleanup and fail
                    foreach ($normalizedFiles as $file) {
                        if (file_exists($file)) unlink($file);
                    }
                    return false;
                }
            }

            // Step 2: Create concat list with normalized files
            $listFile = tempnam($tempDir, 'ffmpeg_merge_');
            $handle = fopen($listFile, 'w');
            foreach ($normalizedFiles as $file) {
                fwrite($handle, "file '" . str_replace("'", "'\\''", $file) . "'\n");
            }
            fclose($handle);

            // Step 3: Concatenate normalized files (can use stream copy since all are identical format)
            $cmd = sprintf(
                'ffmpeg -y -f concat -safe 0 -i %s -c copy %s 2>&1',
                escapeshellarg($listFile),
                escapeshellarg($output)
            );
            exec($cmd, $outputLines, $returnCode);

            // Step 4: Cleanup
            unlink($listFile);
            foreach ($normalizedFiles as $file) {
                if (file_exists($file)) unlink($file);
            }

            return $returnCode === 0 && file_exists($output);

        } catch (Exception $e) {
            // Cleanup on exception
            if (isset($listFile) && file_exists($listFile)) unlink($listFile);
            foreach ($normalizedFiles as $file) {
                if (file_exists($file)) unlink($file);
            }
            throw $e;
        }
    }
    /**
     * Add a watermark image onto a video.
     *
     * @param string   $input         Path to input video
     * @param string   $output        Path where watermarked video will be saved
     * @param string   $watermarkPath Path to watermark image
     * @param string|null $position   Position (top-left, top-right, bottom-left, bottom-right, center, or custom)
     * @param int      $x             X offset in pixels
     * @param int      $y             Y offset in pixels
     * @param int|null $width         Width to resize watermark (null = auto)
     * @param int|null $height        Height to resize watermark (null = auto)
     * @param float    $opacity       Watermark opacity (0 = transparent, 1 = opaque)
     * @return bool True if successful, false otherwise
     */
public function addWatermark(
    string $input,
    string $output,
    string $watermarkPath,
    ?string $position = null,
    int $x = 10,
    int $y = 10,
    ?int $width = null,
    ?int $height = null,
    float $opacity = 1.0
): bool {
    try {
        // Validate inputs
        if (!file_exists($input)) {
            throw new \InvalidArgumentException("Input video file does not exist: $input");
        }
        if (!file_exists($watermarkPath)) {
            throw new \InvalidArgumentException("Watermark file does not exist: $watermarkPath");
        }

        echo "[DEBUG] Opening video: $input\n";
        echo "[DEBUG] Watermark path: $watermarkPath\n";

        // Determine position coordinates
        $posX = $x;
        $posY = $y;
        
        switch ($position) {
            case 'top-left':
                $posX = $x;
                $posY = $y;
                break;
            case 'top-right':
                $posX = "main_w-overlay_w-$x";
                $posY = $y;
                break;
            case 'bottom-left':
                $posX = $x;
                $posY = "main_h-overlay_h-$y";
                break;
            case 'bottom-right':
                $posX = "main_w-overlay_w-$x";
                $posY = "main_h-overlay_h-$y";
                break;
            case 'center':
                $posX = "(main_w-overlay_w)/2";
                $posY = "(main_h-overlay_h)/2";
                break;
            default:
                // Use provided x,y coordinates
                $posX = $x;
                $posY = $y;
                break;
        }

        echo "[DEBUG] Position resolved: x=$posX, y=$posY\n";

        // Build filter components
        $watermarkFilter = "[1:v]";
        $filterChain = [];
        
        // Step 1: Scale watermark if dimensions provided
        if ($width !== null || $height !== null) {
            $scaleW = $width ?? -1;
            $scaleH = $height ?? -1;
            $filterChain[] = "[1:v]scale={$scaleW}:{$scaleH}[scaled]";
            $watermarkFilter = "[scaled]";
        }
        
        // Step 2: Apply opacity if needed
        if ($opacity < 1.0) {
            $filterChain[] = "{$watermarkFilter}format=rgba,colorchannelmixer=aa={$opacity}[transparent]";
            $watermarkFilter = "[transparent]";
        }
        
        // Step 3: Overlay onto main video
        $filterChain[] = "[0:v]{$watermarkFilter}overlay={$posX}:{$posY}[out]";
        
        $filterComplex = implode(';', $filterChain);
        echo "[DEBUG] Filter complex: $filterComplex\n";

        // Build FFmpeg command
        $cmd = sprintf(
            'ffmpeg -y -i %s -i %s -filter_complex %s -map "[out]" -map 0:a? -c:v libx264 -crf 23 -preset fast -c:a copy %s 2>&1',
            escapeshellarg($input),
            escapeshellarg($watermarkPath),
            escapeshellarg($filterComplex),
            escapeshellarg($output)
        );

        echo "[DEBUG] Executing command: $cmd\n";
        
        // Execute command
        $outputLines = [];
        $returnCode = 0;
        exec($cmd, $outputLines, $returnCode);
        
        if ($returnCode !== 0) {
            echo "[ERROR] FFmpeg failed with return code: $returnCode\n";
            echo "[ERROR] FFmpeg output:\n" . implode("\n", $outputLines) . "\n";
            return false;
        }

        $success = file_exists($output) && filesize($output) > 0;
        echo "[DEBUG] Watermark " . ($success ? "applied successfully" : "failed - output file issue") . "\n";
        
        return $success;

    } catch (\Exception $e) {
        echo "[ERROR] Watermark failed: " . $e->getMessage() . "\n";
        return false;
    }
}
/**
 * resize video
 */

public static function resize(string $input, string $output, int $width, int $height, bool $keepAspect = true): bool
{
    // Build the scale filter
    $scaleFilter = $keepAspect
        ? "scale={$width}:{$height}:force_original_aspect_ratio=decrease"
        : "scale={$width}:{$height}";

    // Wrap paths in quotes to handle spaces / backslashes (Windows safe)
    $command = sprintf(
        'ffmpeg -i "%s" -vf "%s" "%s" -y',
        $input,
        $scaleFilter,
        $output
    );

    // Debug log the command
    \Log::info("Running FFmpeg resize", ['command' => $command]);

    // Run command and capture output
    exec($command . ' 2>&1', $outputLines, $exitCode);

    if ($exitCode !== 0) {
        \Log::error("FFmpeg error", [
            'command' => $command,
            'exitCode' => $exitCode,
            'output' => $outputLines,
        ]);
        return false;
    }

    return true;
}



}