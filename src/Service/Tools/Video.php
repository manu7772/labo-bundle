<?php
namespace Aequation\LaboBundle\Service\Tools;

use InvalidArgumentException;
use Stringable;

class Video implements Stringable
{
    public const AUTO_VIDEO_TYPE = '__auto__';

    public string $video_type;
    public string $video_id;
    public string $video_url;
    public array $data;

    public function __construct(
        ?string $video_id_or_rl = null,
        ?string $video_type = null
    )
    {
        $this->reset();
        if(!empty($video_id_or_rl)) {
            $this->setUrlOrId($video_id_or_rl);
        }
        if(!empty($video_type) && $video_type !== static::AUTO_VIDEO_TYPE) {
            $this->setVideoType($video_type);
        }
    }

    public function __toString(): string
    {
        $string = (string) $this->getVideoUrl();
        return empty($string) ? '--- undefined video '.spl_object_id($this).' ---' : $string;
    }

    public function __debugInfo(): array
    {
        return $this->getData();
    }

    public function getData(): array
    {
        return [
            '_toString' => (string) $this,
            'video_type' => $this->video_type ?? null,
            'video_id' => $this->video_id ?? null,
            'video_url' => $this->video_url ?? $this->getVideoUrl(),
            'data' => $this->data ?? null,
            'is_valid' => $this->isValid(),
            'is_enabled' => $this->isEnabled(),
            'url' => $this->getUrl(),
            'label' => $this->getLabel(),
            'template' => $this->getTemplate(),
            'title' => $this->getTitle(),
            'thumbnail' => $this->getThumbnail(),
        ];
    }

    public function isValid(): bool
    {
        return !empty($this->video_type) && !empty($this->video_id) && $this->isEnabled();
    }

    public function reset(): static
    {
        $this->video_type = static::AUTO_VIDEO_TYPE;
        unset($this->video_id);
        unset($this->video_url);
        unset($this->data);
        return $this;
    }

    public function setUrlOrId(string $idOrUrl): bool
    {
        return Encoders::isUrl($idOrUrl)
            ? $this->testAndAddUrl($idOrUrl)
            : $this->setVideoId($idOrUrl);
    }

    public function setVideoUrl(string $url): bool
    {
        if(!Encoders::isUrl($url)) {
            throw new InvalidArgumentException(vsprintf('Error %s line %d: video URL must be a valid URL (got %s).', [__FILE__, __LINE__, json_encode($url)]));
        }
        if(!$this->testAndAddUrl($url)) {
            $this->reset();
            // throw new InvalidArgumentException(vsprintf('Error %s line %d: video URL is not valid or not supported (got %s).', [__FILE__, __LINE__, json_encode($url)]));
        }
        return $this->isValid();
    }

    public function getVideoUrl(): ?string
    {
        return $this->video_url ?? $this->getUrl();
    }

    public function setVideoType(string $type): static
    {
        if($type === static::AUTO_VIDEO_TYPE) {
            return $this;
        }
        $this->video_type = $type;
        $this->data = static::getVideoTypeDescriptions(false, $this->video_type);
        return $this;
    }

    public function getVideoType(): ?string
    {
        return $this->video_type ?? null;
    }

    public function getVideoTypeName(): ?string
    {
        return $this->data['label'] ?? null;
    }

    public function setVideoId(string $video_id): bool
    {
        if(!is_string($video_id) || empty($video_id)) {
            throw new InvalidArgumentException(vsprintf('Error %s line %d: video ID cannot be empty or a URL (got %s).', [__FILE__, __LINE__, json_encode($video_id)]));
        }
        if(Encoders::isUrl($video_id)) {
            $this->setVideoUrl($video_id);
        } else {
            $this->video_id = $this->testIdvalid($video_id) ? $video_id : null;
        }
        return !empty($this->video_id);
    }

    public function getVideoId(): ?string
    {
        return $this->video_id ?? null;
    }

    public static function getTypes(bool $filter = true): array
    {
        return array_keys(static::getVideoTypeDescriptions($filter));
    }

    public static function getTypeChoices(bool $filter = true): array
    {
        // return array_map(fn($v) => $v['label'], static::getVideoTypeDescriptions($filter));
        $videotypes = array_merge([static::AUTO_VIDEO_TYPE => ['label' => 'DÉTECTION AUTOMATIQUE']], static::getVideoTypeDescriptions($filter));
        $choices = array_combine(
            array_map(fn($v) => $v['label'], $videotypes),
            array_keys($videotypes)
        );
        return $choices;
    }

    public function isEnabled(): bool
    {
        return $this->getDataEnabled();
    }

    public function getUrl(): ?string
    {
        return !empty($this->video_url) ? str_replace('{{ video.videoid }}', $this->video_url, $this->getDataUrlTemplate()) : null;
    }

    public function getLabel(): ?string
    {
        return $this->data['label'] ?? null;
    }

    public function getTemplate(): ?string
    {
        return !empty($this->getDataTemplate()) ? preg_replace('/{{\s*video.videoid\s*}}/', $this->video_id, $this->getDataTemplate()) : null;
    }

    public function getTitle(): ?string
    {
        $title = is_callable($this->getDataTitle()) ? $this->getDataTitle()($this->video_id) : $this->getDataTitle();
        return empty($title) ? null : html_entity_decode($title);
    }

    public function getThumbnail(): ?string
    {
        return is_callable($this->getDataThumbnail()) ? $this->getDataThumbnail()($this->video_id) : $this->getDataThumbnail();
    }

    public function testIdvalid(?string $id): bool
    {
        return ($test = $this->getDataIdvalid()) ? $test($id) : (bool) preg_match('/^[a-zA-Z0-9_-]{6,}$/', $id);
    }

    public function testAndAddUrl(string $url): bool
    {
        $found = false;
        if(Encoders::isUrl($url)) {
            $this->video_url = $url;
            foreach (static::getVideoTypeDescriptions(false) as $type => $data) {
                if($video_id = $data['test']($this->video_url)) {
                    $this->setVideoType($type);
                    $this->setVideoId($video_id);
                    $found = true;
                    if($this->isEnabled()) {
                        return $found;
                    }
                    // if not enabled, try next type
                }
            }
        }
        return $found;
    }

    public static function getVideoTypeDescriptions(bool $filter = true, ?string $type = null): array
    {
        $types = [
            'youtube' => [
                'enabled' => true,
                'label' => 'YouTube',
                'template' => '<iframe width="560" height="315" src="https://www.youtube.com/embed/{{ video.videoid }}" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>',
                'url_template' => 'https://www.youtube.com/watch?v={{ video.videoid }}',
                'title' => fn (string $id): ?string => preg_replace('/\s-\sYouTube$/', '', explode('</title>', explode('<title>', (string) @file_get_contents("https://www.youtube.com/watch?v={".$id."}"))[1] ?? '')[0]) ?? null,
                'thumbnail' => fn (string $id, string $quality = 'hqdefault') => "https://img.youtube.com/vi/{$id}/{$quality}.jpg",
                'test' => function (string $url): bool|string {
                    preg_match('/(?:youtube(?:-nocookie)?\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i', $url, $match);
                    return isset($match[1]) && !empty($match[1]) ? $match[1] : false;
                },
                'idvalid' => function (?string $id): bool {
                    return !empty($id) && (bool) preg_match('/^[a-zA-Z0-9_-]{11}$/', $id);
                },
            ],
            'vimeo' => [
                'enabled' => true,
                'label' => 'Viméo',
                'template' => '<iframe width="560" height="315" src="https://player.vimeo.com/video/{{ video.videoid }}" title="Vimeo video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>',
                'url_template' => 'https://vimeo.com/{{ video.videoid }}',
                'title' => fn (string $id): ?string => explode('</title>', explode('<title>', (string) @file_get_contents("https://vimeo.com/{$id}"))[1] ?? '')[0] ?? null,
                'thumbnail' => null,
                'test' => function (string $url): bool|string {
                    preg_match("/(https?:\/\/)?(www\.)?(player\.)?vimeo\.com\/?(showcase\/)*([0-9])?([a-z]*\/)*([0-9]{6,11})[?]?.*/", $url, $match);
                    return isset($match[7]) && !empty($match[7]) ? $match[7] : false;
                },
            ],
            'dailymotion' => [
                'enabled' => true,
                'label' => 'Dailymotion',
                'template' => '<iframe width="560" height="315" src="https://www.dailymotion.com/embed/video/{{ video.videoid }}" title="Dailymotion video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>',
                'url_template' => 'https://www.dailymotion.com/video/{{ video.videoid }}',
                'title' => fn (string $id): ?string => explode('</title>', explode('<title>', (string) @file_get_contents("https://www.dailymotion.com/video/{$id}"))[1] ?? '')[0] ?? null,
                'thumbnail' => null,
                'test' => function (string $url): bool|string {
                    preg_match('/(?:dailymotion\.com\/video|dai\.ly)\/([a-zA-Z0-9]+)/', $url, $match);
                    return isset($match[1]) && !empty($match[1]) ? $match[1] : false;
                },
            ],
            'facebook' => [
                'enabled' => true,
                'label' => 'Facebook Video',
                'template' => '<iframe width="560" height="315" src="https://www.facebook.com/plugins/video.php?href=https%3A%2F%2Fwww.facebook.com%2Ffacebook%2Fvideos%2F{{ video.videoid }}&show_text=0&width=560" title="Facebook video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>',
                'url_template' => 'https://www.facebook.com/videos/{{ video.videoid }}',
                'title' => null,
                'thumbnail' => null,
                'test' => function (string $url): bool|string {
                    preg_match('/(?:facebook\.com\/videos\/)([0-9]+)/', $url, $match);
                    return isset($match[1]) && !empty($match[1]) ? $match[1] : false;
                },
            ],
            'facebook_reel' => [
                'enabled' => true,
                'label' => 'Facebook Reel',
                'template' => '<iframe width="560" height="315" src="https://www.facebook.com/plugins/video.php?href=https%3A%2F%2Fwww.facebook.com%2Ffacebook%2Fvideos%2F{{ video.videoid }}&show_text=0&width=560" title="Facebook video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>',
                'url_template' => 'https://www.facebook.com/reel/{{ video.videoid }}',
                'title' => null,
                'thumbnail' => null,
                'test' => function (string $url): bool|string {
                    preg_match('/(?:facebook\.com\/reel\/)([0-9]+)/', $url, $match);
                    return isset($match[1]) && !empty($match[1]) ? $match[1] : false;
                },
            ],
            'facebook_watch' => [
                'enabled' => true,
                'label' => 'Facebook Watch',
                'template' => '<iframe width="560" height="315" src="https://www.facebook.com/plugins/video.php?href=https%3A%2F%2Fwww.facebook.com%2Ffacebook%2Fvideos%2F{{ video.videoid }}&show_text=0&width=560" title="Facebook video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>',
                'url_template' => 'https://fb.watch/{{ video.videoid }}',
                'title' => null,
                'thumbnail' => null,
                'test' => function (string $url): bool|string {
                    preg_match('/^https:\/\/fb\.watch\/(\w+)\/?/', $url, $match);
                    // dump($match);
                    return isset($match[1]) && !empty($match[1]) ? $match[1] : false;
                },
            ],
            'instagram' => [
                'enabled' => true,
                'label' => 'Instagram',
                'template' => '<iframe width="560" height="315" src="https://www.instagram.com/p/{{ video.videoid }}/embed" title="Instagram video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>',
                'url_template' => 'https://www.instagram.com/p/{{ video.videoid }}/',
                'title' => fn (string $id): ?string => explode('</title>', explode('<title>', (string) @file_get_contents("https://www.instagram.com/p/{$id}"))[1] ?? '')[0] ?? null,
                'thumbnail' => null,
                'test' => function (string $url): bool|string {
                    preg_match('/(?:instagram\.com\/p\/)([a-zA-Z0-9_-]+)/', $url, $match);
                    return isset($match[1]) && !empty($match[1]) ? $match[1] : false;
                },
            ],
            '_custom' => [
                'enabled' => false,
                'label' => 'Personnalisé',
                'template' => null,
                'url_template' => null,
                'title' => fn (string $id): ?string => explode('</title>', explode('<title>', (string) @file_get_contents("https://www.youtube.com/watch?v={$id}"))[1] ?? '')[0] ?? null,
                'thumbnail' => null,
                'test' => fn (string $url): bool|string => true,
            ],
        ];
        if($filter) {
            $types = array_filter($types, fn($v) => $v['enabled'] ?? true);
        }
        return !empty($type) ? $types[$type] ?? [] : $types;
    }


    /********************************************************************************************************************/
    /* SETTERS
    /********************************************************************************************************************/

    public function setDataEnabled(bool $enabled): static
    {
        $this->data['enabled'] = $enabled;
        return $this;
    }   

    public function setDataLabel(string $label): static
    {
        $this->data['label'] = $label;
        return $this;
    }

    public function setDataTemplate(string $template): static
    {
        $this->data['template'] = $template;
        return $this;
    }

    public function setDataUrlTemplate(string $url_template): static
    {
        $this->data['url_template'] = $url_template;
        return $this;
    }

    public function setDataTitle(string|callable $title): static
    {
        $this->data['title'] = html_entity_decode($title);
        return $this;
    }

    public function setDataThumbnail(string|callable $thumbnail): static
    {
        $this->data['thumbnail'] = $thumbnail;
        return $this;
    }

    public function setDataTest(callable $test): static
    {
        $this->data['test'] = $test;
        return $this;
    }

    public function setDataIdvalid(callable $idvalid): static
    {
        $this->data['idvalid'] = $idvalid;
        return $this;
    }


    /********************************************************************************************************************/
    /* GETTERS
    /********************************************************************************************************************/

    public function getDataEnabled(): bool
    {
        return $this->data['enabled'] ?? true;
    }

    public function getDataLabel(): ?string
    {
        return $this->data['label'] ?? null;
    }

    public function getDataTemplate(): ?string
    {
        return $this->data['template'] ?? null;
    }

    public function getDataUrlTemplate(): ?string
    {
        return $this->data['url_template'] ?? null;
    }

    public function getDataTitle(): string|callable|null
    {
        return $this->data['title'] ?? null;
    }

    public function getDataThumbnail(): string|callable|null
    {
        return $this->data['thumbnail'] ?? null;
    }

    public function getDataTest(): ?callable
    {
        return $this->data['test'] ?? null;
    }

    public function getDataIdvalid(): ?callable
    {
        return $this->data['idvalid'] ?? null;
    }

}