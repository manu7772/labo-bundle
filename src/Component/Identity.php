<?php
namespace Aequation\LaboBundle\Component;

use Aequation\LaboBundle\Service\Tools\Strings;
use Exception;

class Identity
{

    public const ENTREPRISE_PARAM_NAME = 'entreprise';
    public const REGEX_FIND_ENTREPRISE = '/^(entreprise\.)/';

    private array $entreprise = [];

    public function __construct(
        array $data
    ) {
        if(preg_match(static::REGEX_FIND_ENTREPRISE, array_key_first($data))) {
            // list of params
            foreach ($data as $key => $value) {
                $key = preg_replace(static::REGEX_FIND_ENTREPRISE, '', $key);
                if(preg_match('/\./', $key)) {
                    // data as array
                    $keys = explode('.', $key, 2);
                    $this->entreprise[$keys[0]][preg_replace('/[-]+/', '_', $keys[1])] = $value;
                } else {
                    $this->entreprise[$key] = $value;
                }
            }
        } else {
            // array
            $this->entreprise = $data;
        }
    }

    public function __get($name)
    {
        $value = $this->entreprise[$name] ?? null;
        return is_string($value)
            ? Strings::markup($value)
            : $value;
    }

    public function __isset($name): bool
    {
        return true;
        // $isset = array_key_exists($name, $this->entreprise);
        // if(!$isset) throw new Exception(vsprintf('Error %s line %d: la valeur %s n\'existe pas. Valeurs proposées : %s.',[__METHOD__, __LINE__, $name, json_encode(array_keys($this->entreprise))]));
        // return $isset;
    }

}