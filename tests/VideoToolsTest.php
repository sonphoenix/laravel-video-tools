<?php

namespace SonPhoenix\VideoTools\Tests;

use SonPhoenix\VideoTools\VideoTools;

class VideoToolsTest extends TestCase
{
    protected $tempVideo;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a temporary video file for testing
        $this->tempVideo = sys_get_temp_dir() . '/test_video.mp4';
        // Create a simple test video (1 second, 320x240, red color)
        exec('ffmpeg -y -f lavfi -i color=c=red:s=320x240:d=1 ' . escapeshellarg($this->tempVideo));
    }

    protected function tearDown(): void
    {
        // Clean up temporary files
        if (file_exists($this->tempVideo)) {
            unlink($this->tempVideo);
        }
        parent::tearDown();
    }

    /** @test */
    public function parse_duration_works()
    {
        $reflection = new \ReflectionClass(VideoTools::class);
        $method = $reflection->getMethod('parseDuration');
        $method->setAccessible(true);

        $videoTools = new VideoTools();

        $this->assertEquals(65, $method->invokeArgs($videoTools, ['1:05']));
        $this->assertEquals(3665, $method->invokeArgs($videoTools, ['1:01:05']));
        $this->assertEquals(30, $method->invokeArgs($videoTools, ['30']));
    }

    /** @test */
    public function it_can_trim_video()
    {
        $output = sys_get_temp_dir() . '/trimmed.mp4';
        $videoTools = new VideoTools();
        $result = $videoTools->trim($this->tempVideo, $output, '0', '0:00:1');

        $this->assertTrue($result);
        $this->assertFileExists($output);

        // Cleanup
        if (file_exists($output)) unlink($output);
    }

    /** @test */
    public function it_can_generate_thumbnail()
    {
        $output = sys_get_temp_dir() . '/thumbnail.jpg';
        $videoTools = new VideoTools();
        $resultPath = $videoTools->thumbnail($this->tempVideo, $output);

        $this->assertEquals($output, $resultPath);
        $this->assertFileExists($output);

        // Cleanup
        if (file_exists($output)) unlink($output);
    }

    /** @test */
    public function it_can_extract_audio()
    {
        $output = sys_get_temp_dir() . '/audio.mp3';
        $videoTools = new VideoTools();
        $result = $videoTools->extractAudio($this->tempVideo, $output);

        $this->assertTrue($result);
        $this->assertFileExists($output);

        // Cleanup
        if (file_exists($output)) unlink($output);
    }

    /** @test */
    public function it_can_merge_videos()
    {
        // Create a second test video
        $video1 = sys_get_temp_dir() . '/test_video1.mp4';
        exec('ffmpeg -y -f lavfi -i color=c=green:s=320x240:d=1 ' . escapeshellarg($video1));
        
        $video2 = sys_get_temp_dir() . '/test_video2.mp4';
        exec('ffmpeg -y -f lavfi -i color=c=blue:s=320x240:d=1 ' . escapeshellarg($video2));

        $output = sys_get_temp_dir() . '/merged.mp4';
        $videoTools = new VideoTools();
        $result = $videoTools->merge([$video1, $video2], $output);

        $this->assertTrue($result);
        $this->assertFileExists($output);

        // Cleanup
        if (file_exists($video1)) unlink($video1);
        if (file_exists($video2)) unlink($video2);
        if (file_exists($output)) unlink($output);
    }

    /** @test */
    public function it_can_add_watermark()
    {
        $output = sys_get_temp_dir() . '/watermarked.mp4';
        
        // Create a simple watermark image
        $watermark = sys_get_temp_dir() . '/watermark.png';
        // Create dummy watermark
        exec('ffmpeg -y -f lavfi -i color=c=red:s=50x50:d=1 ' . escapeshellarg($watermark));

        $videoTools = new VideoTools();
        $result = $videoTools->addWatermark($this->tempVideo, $output, $watermark, 'top-left');

        $this->assertTrue($result);
        $this->assertFileExists($output);

        // Cleanup
        if (file_exists($watermark)) unlink($watermark);
        if (file_exists($output)) unlink($output);
    }

    /** @test */
    public function it_can_resize_video()
    {
        $output = sys_get_temp_dir() . '/resized.mp4';
        $videoTools = new VideoTools();
        $result = VideoTools::resize($this->tempVideo, $output, 160, 120);

        $this->assertTrue($result);
        $this->assertFileExists($output);

        // Cleanup
        if (file_exists($output)) unlink($output);
    }
}