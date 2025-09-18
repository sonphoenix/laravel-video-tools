
# Laravel Video Tools

A Laravel package for video processing powered by FFmpeg.
It supports trimming, extracting audio, generating thumbnails, merging videos, resizing, and adding watermarks.

## Installation

Install the package via Composer:
```bash
composer require sonphoenix/laravel-video-tools
```

## Requirements

PHP 8.1 or higher

FFmpeg installed on your system

Laravel 9.x or higher
## Usage/Examples

```php
use SonPhoenix\VideoTools\Facades\VideoTools;

// Trim a video
VideoTools::trim('input.mp4', 'output.mp4', '0:01:30', '0:00:15');

// Extract a thumbnail
VideoTools::thumbnail('input.mp4', 'thumbnail.jpg', '0:00:05');

// Extract audio
VideoTools::extractAudio('input.mp4', 'output.mp3', 'mp3');

// Merge videos
VideoTools::merge(['video1.mp4', 'video2.mp4'], 'merged.mp4');

// Add watermark
VideoTools::addWatermark('input.mp4', 'output.mp4', 'watermark.png', 'bottom-right');

// Resize video
VideoTools::resize('input.mp4', 'output.mp4', 640, 480, true);
```


## API Reference

#### trim or cut video

```http
trim(input, output, start, duration)
```

| Parameter  | Type   | Description                      |
| :--------- | :----- | :------------------------------- |
| `input`    | string | Path to input video              |
| `output`   | string | Path to save trimmed video       |
| `start`    | string | Start time (`hh:mm:ss`, `mm:ss`) |
| `duration` | string | Duration of clip (`hh:mm:ss`)    |

#### examples
```http
VideoTools::trim('input.mp4', 'output.mp4', '0:01:30', '0:00:15');
```

#### get thumbnail from video

```http
thumbnail(inputPath, outputPath, time)
```

| Parameter    | Type   | Description                           |
| :----------- | :----- | :------------------------------------ |
| `inputPath`  | string | Path to input video                   |
| `outputPath` | string | Path to save thumbnail                |
| `time`       | string | Time position (`hh:mm:ss` or seconds) |

#### examples
```http
VideoTools::thumbnail('input.mp4', 'thumbnail.jpg', '0:00:05');
```

#### get audio from video

```http
extractAudio(input, output, format)
```

| Parameter    | Type   | Description                           |
| :----------- | :----- | :------------------------------------ |
| `inputPath`  | string | Path to input video                   |
| `outputPath` | string | Path to save thumbnail                |
| `time`       | string | Time position (`hh:mm:ss` or seconds) |

#### examples
```http
VideoTools::extractAudio('input.mp4', 'output.mp3', 'mp3');
```

#### add watermark to video

```http
addWatermark(input, output, watermarkPath, position, x, y, width, height, opacity)
```

| Parameter       | Type   | Description                                                                 |
| :-------------- | :----- | :-------------------------------------------------------------------------- |
| `input`         | string | Path to input video                                                         |
| `output`        | string | Path to save watermarked video                                              |
| `watermarkPath` | string | Path to watermark image                                                     |
| `position`      | string | Position (`top-left`, `top-right`, `bottom-left`, `bottom-right`, `center`) |
| `x`             | int    | X offset in pixels                                                          |
| `y`             | int    | Y offset in pixels                                                          |
| `width`         | int    | Width to resize watermark (optional)                                        |
| `height`        | int    | Height to resize watermark (optional)                                       |
| `opacity`       | float  | Watermark opacity (0 = transparent, 1 = opaque)                             |

#### examples
```http
// Position watermark at bottom-right
VideoTools::addWatermark('input.mp4', 'output.mp4', 'watermark.png', 'bottom-right');

// Custom position and size
VideoTools::addWatermark(
    'input.mp4', 
    'output.mp4', 
    'watermark.png', 
    null, 
    50, 
    50, 
    100, 
    50, 
    0.7
);
```

#### merge or fusion a bunch of videos

```http
merge(inputs, output)
```

| Parameter | Type   | Description                |
| :-------- | :----- | :------------------------- |
| `inputs`  | array  | Array of input video paths |
| `output`  | string | Path to save merged video  |

#### examples
```http
VideoTools::merge(['video1.mp4', 'video2.mp4'], 'merged.mp4');
```

#### resize a video

```http
resize(input, output, width, height, keepAspect)
```

| Parameter    | Type   | Description                    |
| :----------- | :----- | :----------------------------- |
| `input`      | string | Path to input video            |
| `output`     | string | Path to save resized video     |
| `width`      | int    | Target width                   |
| `height`     | int    | Target height                  |
| `keepAspect` | bool   | Keep aspect ratio (true/false) |

#### examples
```http
VideoTools::resize('input.mp4', 'output.mp4', 1280, 720, true);
```






## Authors

- [@FERRADJ OMAR](https://github.com/sonphoenix)


## License

[MIT](https://choosealicense.com/licenses/mit/)

