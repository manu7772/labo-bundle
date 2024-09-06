<?php
namespace Aequation\LaboBundle\Component;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * Time history READER functionalities of array of data
 */
class Timehistory
{

    protected array $data = [];

    /**
     * Constructor
     * $timekey is where to find the date if date is not the keys (as string) of the $data array
     * @param array $data
     * @param string|null $timekey
     */
    public function __construct(
        array $data,
        protected ?string $timekey = null,
        protected bool $useMicroseconds = false,
    ) {
        foreach ($data as $key => $value) {
            $time = empty($this->timekey) ? $key : $value[$this->timekey];
            if(!($time instanceof DateTimeInterface)) {
                if($this->useMicroseconds) {
                    // Use microseconds
                    $time = DateTimeImmutable::createFromFormat('U.u', $time);
                    $index = intval($time->format('Uu'));
                } else {
                    // Use seconds
                    $time = new DateTimeImmutable($time);
                    $index = intval($time->format('Uv'));
                }
            } else {
                $index = intval($time->format($this->useMicroseconds ? 'Uu' : 'Uv'));
            }
            $this->data[$index] = [
                'key' => $key,
                'data' => $value,
                'time' => $time,
            ];
        }
    }

    public function getNewer(): mixed
    {
        $current = null;
        foreach ($this->data as $value) {
            if(!$current || $current['time'] < $value['time']) $current = $value;
        }
        return $current['data'];
    }

    public function getOlder(): mixed
    {
        $current = null;
        foreach ($this->data as $value) {
            if(!$current || $current['time'] > $value['time']) $current = $value;
        }
        return $current['data'];
    }

    public function getBetween(
        string|DateTimeInterface $start,
        string|DateTimeInterface $end,
        string $sorted = 'DESC',
    ): array
    {
        return $this->sortData(
            data: array_filter($this->data, function($value) use ($start, $end) {
                return $start < $value['time'] && $end > $value['time'];
            }),
            sorted: $sorted
        );
    }

    public function getBefore(
        string|DateTimeInterface $before,
        string $sorted = 'DESC',
    ): array
    {
        return $this->sortData(
            data: array_filter($this->data, function($value) use ($before) {
                return $before > $value['time'];
            }),
            sorted: $sorted
        );
    }

    public function getAfter(
        string|DateTimeInterface $after,
        string $sorted = 'DESC',
    ): array
    {
        return $this->sortData(
            data: array_filter($this->data, function($value) use ($after) {
                return $after < $value['time'];
            }),
            sorted: $sorted
        );
    }

    public function getSorted(string $sorted = 'DESC'): array
    {
        return $this->sortData(data: $this->data, sorted: $sorted, onlydata: true);
    }

    protected function sortData(array $data, string $sorted = 'DESC', bool $onlydata = false): array
    {
        if($sorted === 'DESC') {
            ksort($data);
        } else {
            krsort($data);
        }
        return $onlydata
            ? $this->getOnlydata($data)
            : $data;
    }

    protected function getOnlydata(array $data = null): array
    {
        $onlydata = [];
        $data ??= $this->data;
        foreach ($data as $ts => $value) {
            $onlydata[$value['key']] = $value['data'];
        }
        return $onlydata;
    }


}