<?php

namespace App\Domain\Worker\Media;

use FFMpeg\Coordinate\Dimension;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use FFMpeg\Filters\Video\ResizeFilter;
use FFMpeg\Format\Video\X264;
use FFMpeg\Media\Video;
use RuntimeException;
use Throwable;

/**
 * Checks and converts SkillShow uploads with ffmpeg: real duration from the file (never the
 * browser's word for it), a web-safe H.264/AAC MP4 at most 720 px tall, and a poster frame.
 */
class SkillShowVideoProcessor
{
    private const MAX_HEIGHT = 720;

    /**
     * @return array{durationMs: int, hasVideo: bool}|null Null when ffprobe cannot read the file.
     */
    public function inspect(string $path): ?array
    {
        try {
            $probe = $this->probe();
            $format = $probe->format($path);
            $hasVideo = $probe->streams($path)->videos()->count() > 0;

            return [
                'durationMs' => (int) round((float) $format->get('duration') * 1000),
                'hasVideo' => $hasVideo,
            ];
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Writes the converted video and a poster image to the given local paths.
     */
    public function convert(string $source, string $videoPath, string $posterPath, int $durationMs): void
    {
        $ffmpeg = FFMpeg::create($this->binaries(), null, $this->probe());
        $video = $ffmpeg->open($source);

        if (! $video instanceof Video) {
            throw new RuntimeException('The SkillShow upload has no video stream.');
        }

        $dimensions = $this->probe()->streams($source)->videos()->first()?->getDimensions();

        if ($dimensions !== null && $dimensions->getHeight() > self::MAX_HEIGHT) {
            $width = (int) (round($dimensions->getWidth() * self::MAX_HEIGHT / $dimensions->getHeight() / 2) * 2);
            $video->filters()->resize(new Dimension($width, self::MAX_HEIGHT), ResizeFilter::RESIZEMODE_INSET, true);
        }

        $format = (new X264('aac'))->setKiloBitrate(1200)->setAudioKiloBitrate(96)
            ->setAdditionalParameters(['-movflags', '+faststart', '-pix_fmt', 'yuv420p']);
        $video->save($format, $videoPath);

        // One second in, or halfway through a very short clip.
        $converted = $ffmpeg->open($videoPath);

        if ($converted instanceof Video) {
            $converted->frame(TimeCode::fromSeconds(min(1, $durationMs / 2000)))->save($posterPath);
        }
    }

    private function probe(): FFProbe
    {
        return FFProbe::create($this->binaries());
    }

    /**
     * @return array<string, mixed>
     */
    private function binaries(): array
    {
        return [
            'ffmpeg.binaries' => config('laravel-ffmpeg.ffmpeg.binaries'),
            'ffprobe.binaries' => config('laravel-ffmpeg.ffprobe.binaries'),
            'timeout' => 300,
            'ffmpeg.threads' => 2,
        ];
    }
}
