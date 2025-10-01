<?php
namespace Aequation\LaboBundle\Service\Tools;

use InvalidArgumentException;

class Video
{
    public const AUTO_VIDEO_TYPE = '__auto__';

    public string $video_type;
    public string $video_id;
    public array $data;
    public string $url;

    // public static function new(string $idOrUrl, ?string $type = null): self
    // {
    //     $types = static::getTypes(false);
    //     $url = Encoders::isUrl($idOrUrl) ? $idOrUrl : null;
    //     if(in_array($type, $types, true)) {
    //         // Type is specified
    //         $instance = new self();
    //         $instance->setVideoType($type);
    //         $instance->setVideoId(Encoders::isUrl($idOrUrl) ? $instance->testUrl($idOrUrl) : $idOrUrl);
    //         // $instance->setUrl($idOrUrl);
    //     } else {
    //         // Type is not specified, try to find it with the URL
    //         if(!Encoders::isUrl($idOrUrl)) {
    //             // throw new InvalidArgumentException(vsprintf('Error %s line %d: when video type is not specified, a URL must be provided (got %s).', [__FILE__, __LINE__, json_encode($idOrUrl)]));
    //             $instance = new self();
    //             $instance->setVideoId($idOrUrl);
    //         }
    //         foreach (static::getVideoTypeDescriptions(false) as $type => $data) {
    //             if($video_id = $data['test']($idOrUrl)) {
    //                 $instance = new self();
    //                 $instance->setVideoType($type);
    //                 $instance->setVideoId($video_id);
    //                 if($instance->isEnabled()) {
    //                     break;
    //                 }
    //                 // if not enabled, try next type
    //             }
    //         }
    //     }
    //     return $instance ?? new self();
    // }

    public function isValid(): bool
    {
        return !empty($this->video_type) && !empty($this->video_id) && $this->isEnabled();
    }

    public function setUrlOrId(string $idOrUrl): static
    {
        if(!$this->testUrl($idOrUrl)) {
            $this->setVideoId($idOrUrl);
        }
        return $this;
    }

    public function setVideoType(string $type): static
    {
        if($type === static::AUTO_VIDEO_TYPE) {
            return $this;
        }
        $this->data = static::getVideoTypeDescriptions(false, $type);
        $this->video_type = $type;
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

    public function setVideoId(string $video_id): static
    {
        if(!is_string($video_id) || empty($video_id) || Encoders::isUrl($video_id)) {
            throw new InvalidArgumentException(vsprintf('Error %s line %d: video ID cannot be empty or a URL (got %s).', [__FILE__, __LINE__, json_encode($video_id)]));
        }
        $this->video_id = $video_id;
        return $this;
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

    // public function setUrl(?string $url = null): static
    // {
    //     if(empty($url)) {
    //         if(empty($this->getDataUrlTemplate())) {
    //             throw new InvalidArgumentException(vsprintf('Error %s line %d: cannot generate URL, no URL template defined for this video type %s.', [__FILE__, __LINE__, json_encode($this->video_type ?? null)]));
    //         }
    //         $this->url = str_replace('{{ video.videoid }}', $this->video_id, $this->getDataUrlTemplate());
    //         return $this;
    //     }
    //     if(!Encoders::isUrl($url)) {
    //         throw new InvalidArgumentException(vsprintf('Error %s line %d: URL must be a valid URL (got %s).', [__FILE__, __LINE__, json_encode($url)]));
    //     }
    //     $this->url = $url;
    //     return $this;
    // }

    // public function getDefaultUrl(): ?string
    // {
    //     return !empty($this->getDataUrlTemplate()) ? str_replace('{{ video.videoid }}', $this->video_id, $this->getDataUrlTemplate()) : null;
    // }

    public function getUrl(): ?string
    {
        return str_replace('{{ video.videoid }}', $this->video_id, $this->getDataUrlTemplate());
        // return $this->url ?? null;
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
        return is_callable($this->getDataTitle()) ? $this->getDataTitle()($this->video_id) : $this->getDataTitle();
    }

    public function getThumbnail(): ?string
    {
        return is_callable($this->getDataThumbnail()) ? $this->getDataThumbnail()($this->video_id) : $this->getDataThumbnail();
    }

    public function testUrl(string $url): bool
    {
        $found = false;
        if(Encoders::isUrl($url)) {
            foreach (static::getVideoTypeDescriptions(false) as $type => $data) {
                if($video_id = $data['test']($url)) {
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
                'title' => fn (string $id) => html_entity_decode(explode('</title>', explode('<title>', file_get_contents("https://www.youtube.com/watch?v={$id}"))[1])[0]),
                'thumbnail' => fn (string $id, string $quality = 'hqdefault') => "https://img.youtube.com/vi/{$id}/{$quality}.jpg",
                'test' => function (string $url): bool|string {
                    preg_match('/(?:youtube(?:-nocookie)?\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i', $url, $match);
                    return isset($match[1]) && !empty($match[1]) ? $match[1] : false;
                },
            ],
            'vimeo' => [
                'enabled' => true,
                'label' => 'Viméo',
                'template' => '<iframe width="560" height="315" src="https://player.vimeo.com/video/{{ video.videoid }}" title="Vimeo video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>',
                'url_template' => 'https://vimeo.com/{{ video.videoid }}',
                'title' => fn (string $id) => explode('</title>', explode('<title>', file_get_contents("https://vimeo.com/{$id}"))[1])[0],
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
                'title' => fn (string $id) => explode('</title>', explode('<title>', file_get_contents("https://www.youtube.com/watch?v={$id}"))[1])[0],
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
                    dump($match);
                    return isset($match[1]) && !empty($match[1]) ? $match[1] : false;
                },
            ],
            'instagram' => [
                'enabled' => true,
                'label' => 'Instagram',
                'template' => '<iframe width="560" height="315" src="https://www.instagram.com/p/{{ video.videoid }}/embed" title="Instagram video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>',
                'url_template' => 'https://www.instagram.com/p/{{ video.videoid }}/',
                'title' => fn (string $id) => explode('</title>', explode('<title>', file_get_contents("https://www.youtube.com/watch?v={$id}"))[1])[0],
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
                'title' => fn (string $id) => explode('</title>', explode('<title>', file_get_contents("https://www.youtube.com/watch?v={$id}"))[1])[0],
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
        $this->data['title'] = $title;
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

}