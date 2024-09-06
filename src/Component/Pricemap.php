<?php
namespace Aequation\LaboBundle\Component;

use Aequation\LaboBundle\Service\Tools\Mesures;
use \IteratorAggregate;
use \ArrayAccess;
use \Traversable;
use \ArrayIterator;

class Pricemap implements IteratorAggregate, ArrayAccess
{

    public function __construct(
        protected array $data = []
    )
    {
        $this->setData($data);
    }

    protected function computePrice(string $expression, int $pers): float
    {
        $value = 0;
        if($pers < 0) $pers = 0;
        try {
            $eval = '$value = '.preg_replace('/(pers)/', '$pers', $expression).';';
            eval($eval);
            // dd($this, $expression, $pers, $eval, $value);
        } catch (\Throwable $th) {
            throw $th;
        }
        return floatval($value);
    }

    public function getPrice(int $pers, bool $formated = false): float|string
    {
        $price = [0];
        foreach (array_reverse($this->organizeData(), true) as $key => $value) {
            if(is_int($key) && $pers >= $key) {
                $price[] = $this->computePrice($value, $pers);
                break;
            }
        }
        if(isset($this->data['min'])) {
            $min = $this->computePrice($this->data['min'], $pers);
            if(end($price) < $min) $price[] = $min;
        }
        if(isset($this->data['max'])) {
            $max = $this->computePrice($this->data['max'], $pers);
            if(end($price) > $max) $price[] = $max;
        }
        // dd($this, $price, number_format(floatval(end($price)), 2, ','));
        return $formated
            ? number_format(floatval(end($price)), 2, ',')
            : floatval(end($price))
            ;
    }

    public function setData(
        array $data
    ): static
    {
        foreach ($data as $key => $value) {
            $this->offsetSet($key, $value);
        }
        return $this;
    }
    
    protected function organizeData(): array
    {
        $this->data = array_filter($this->data, function ($data) {
            return !preg_match('/:/', $data);
        });
        if(isset($this->data['min']) && isset($this->data['max'])) {
            $min = $this->data['min'];
            $max = $this->data['max'];
            if (Mesures::sortMinMax($min, $max)) {
                $this->data['min'] = $min;
                $this->data['max'] = $max;
            }
        }
        ksort($this->data);
        // uksort($this->data, function($a, $b) {
        //     if(is_string($a)) return $b === "min" ? 0 : 1;
        //     if(is_string($b)) return $a === "min" ? 0 : 1;
        //     // if($a === "min") return 2;
        //     // if($b === "min") return -3;
        //     // if($a === "max") return 3;
        //     // if($b === "max") return -2;
        //     return $a > $b ? 10 : -10;
        // });
        foreach ($this->data as $key => $value) {
            $this->data[$key] = trim(preg_replace(['/\\s{2,}/','/\\s*x\\s*/','/\\s*\\+\\s*/','/\\s*-\\s*/','/\\s*,\\s*/'], [' ',' * ',' + ',' - ','.'], $value));
        }
        return $this->data;
    }

    public function toArray(): array
    {
        $array = [];
        foreach ($this->organizeData() as $key => $value) {
            $array[] = $key.' : '.$value;
        }
        return $array;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->organizeData());
    }

    public function getData(): array
    {
        return $this->organizeData();
    }

    public function offsetExists(
        mixed $offset,
    ): bool
    {
        return array_key_exists($offset, $this->data);
    }

    public function offsetGet(
        mixed $offset,
    ): mixed
    {
        return $this->data[$offset];
    }

    public function offsetSet(
        mixed $offset,
        mixed $value,
    ): void
    {
        $vals = preg_split('/\\s*:\\s*/', trim($value), 2);
        // $vals = [];
        // foreach(preg_split('/\\s*:\\s*/', trim($value), 2) as $v) {
        //     $vals[] = trim($v);
        // }
        if(count($vals) > 1) {
            switch (true) {
                case preg_match('/^-?\\d+$/', $vals[0]):
                    $this->data[abs(intval(trim($vals[0])))] = $vals[1];
                    break;
                case strtolower($vals[0]) === 'min':
                    $this->data['min'] = $vals[1];
                    break;
                case strtolower($vals[0]) === 'max':
                    $this->data['max'] = $vals[1];
                    break;
            }
            $this->organizeData();
        }
    }

    public function offsetUnset(
        mixed $offset,
    ): void
    {
        // if($this->offsetExists($offset)) {
            unset($this->data[$offset]);
            $this->organizeData();
        // }
    }


    protected function toExamples(int $from, int $to): string
    {
        $html = '<h4>Examples</h4><ul>';
        for ($i=$from; $i <= $to; $i++) { 
            $html .= '<li>'.$i.' pers. = '.$this->getPrice($i, true).'€</li>';
        }
        $html .= '</ul>';
        return $html;
    }

    public function toDescription(
        string $format = 'array',
        array|bool $example = false,
    ): mixed
    {
        $addexample = '';
        if($example) {
            if(is_bool($example)) $example = [0,24];
            $addexample = $this->toExamples($example[0], $example[1]);
        }
        switch ($format) {
            case 'html:ul':
                // html <ul><li>
                $html = '<ul style="padding-left: 1.2em;"><li>'.implode('</li><li>', $this->data).'</li></ul>';
                $html .= $addexample;
                return $html;
                break;
            case 'html:table':
                // html <table>
                $html = '<table class="table table-bordered table-responsive table-sm table-hover">';
                $html .= '<thead><tr><th>Personnes et plus</th><th>Calcul du tarif</th></tr></thead><tbody>';
                foreach ($this->data as $key => $value) {
                    $etplus = is_string($key) ? '' : '<i class="text-muted"> et +</i>';
                    $html .= '<tr><td class="text-white">'.$key.$etplus.'</td><td>'.$value.'</td></tr>';
                }
                $html .= '</tbody></table>';
                $html .= $addexample;
                return $html;
                break;
            default:
                // array
                $description = [];
                foreach ($this->data as $key => $value) {
                    $description[] = $key.' et + ---> '.$value;
                }
                return $description;
                break;
        }
    }

}