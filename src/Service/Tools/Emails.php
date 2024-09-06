<?php
namespace Aequation\LaboBundle\Service\Tools;

use Aequation\LaboBundle\Service\Base\BaseService;
use Aequation\LaboBundle\Component\Opresult;
use Aequation\LaboBundle\Model\User;
use Symfony\Component\Mime\Address;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\MessageIDValidation;
use Egulias\EmailValidator\Validation\RFCValidation;
use Exception;

use function Symfony\Component\String\u;

class Emails extends BaseService
{

    /** ***********************************************************************************
     * EMAILS
     *************************************************************************************/

    public static function isEmailValid(mixed $email)
    {
        if(!is_string($email)) return false;
        if (!class_exists(EmailValidator::class)) {
            return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        }
        $validator = new EmailValidator();
        return $validator->isValid($email, class_exists(MessageIDValidation::class) ? new MessageIDValidation() : new RFCValidation());
    }

    public static function emailToFakeName(
        string $email,
        string $whitespace = ' '
    ): string
    {
        $name = Strings::getBefore($email, '@', false);
        return u($name)->replaceMatches('/[\W\d\s_]+/', $whitespace)->camel();
    }

    /**
     * Remove double emails in list of Address
    * @param array &$addresses
    * @return bool (true if some emails where removed)
    */
    public static function emails_unique_list(array &$addresses): bool
    {
        $nbaddr = count($addresses);
        $newlist = [];
        foreach ($addresses as $address) {
            if($address instanceof Address) {
                $newlist[$address->getAddress()] = $address;
            }
        }
        $addresses = array_values($newlist);
        return count($addresses) !== $nbaddr;
    }

    /**
     * Is array like [email => name] or [name => email]
    * If $conformize is true, set the email as key if is not
    * @param mixed $data
    * @param boolean $conformize = false
    * @return boolean
    */
    public static function isArrayEmail(mixed &$data, $conformize = false): bool
    {
        if(!is_array($data)) return false;
        $is = count($data) === 1 && (static::isEmailValid(array_key_first($data)) xor static::isEmailValid(reset($data)));
        // if(!$is) dd($data, [reset($data) => array_key_first($data)]);
        if($is && $conformize && static::isEmailValid(reset($data))) {
            $data = [reset($data) => array_key_first($data)];
        }
        return $is;
    }

    public static function getUserAddress(User $user): Address
    {
        return new Address($user->getEmail(), $user->getEmailName());
    }

    /**
     * Get Array of Address objects from Emails list with different formats
    * @param array|string|Address|User $data
    * @param boolean $remove_doubles
    * @param string $name
    * @return array
    */
    public static function getEmailAddressObjects(
        array|string|Address|User $data,
        bool $remove_doubles = true,
        string $name = '',
    ): array
    {
        $cpdata = $data;
        switch (true) {
            case $data instanceof User:
                $addresses = [static::getUserAddress($data)];
                break;
            case $data instanceof Address:
                $addresses = [$data];
                break;
            case static::isEmailValid($data):
                $addresses = [new Address($data, $name ?? '')];
                break;
            case static::isArrayEmail($cpdata, true):
                $addresses = [new Address(array_key_first($cpdata), reset($cpdata))];
            break;
            case is_array($data):
                $addresses = [];
                foreach ($data as $key => $value) {
                    $array_email = [$key => $value];
                    if(static::isArrayEmail($array_email, true)) {
                        $value = array_key_first($array_email);
                        $key = reset($array_email);
                    }
                    $addresses = array_merge($addresses, static::getEmailAddressObjects(data: $value, remove_doubles: false, name: is_string($key) ? $key : ''));
                }
                break;
            default:
                throw new Exception(vsprintf('Invalid data for making %s object: %s', [Address::class, json_encode($data)]));
                break;
        }
        if($remove_doubles) static::emails_unique_list($addresses);
        static::controlEmailAddressList($addresses, true);
        return $addresses;
    }

    /**
     * Get array of <email => name> from Emails list with different formats
    * Used for JSON and serialization
    * @param array|string|Address|User $data
    * @return array
    */
    public static function getEmailAddressArray(
        array|string|Address|User $data,
        string $name = '',
    ): array
    {
        $cpdata = is_string($data) ? [$data => $name] : null;
        if($data instanceof User) $data = static::getUserAddress($data);
        switch (true) {
            case static::isArrayEmail($cpdata, true):
                $addresses = $cpdata;
                break;
            case static::isArrayEmail($data, true):
                $addresses = $data;
                break;
            case $data instanceof Address:
                $addresses = [$data->getAddress() => $data->getName()];
                break;
            case static::isEmailValid($data):
                $addresses = [$data => $name ?? ''];
                break;
            case is_array($data):
                $addresses = [];
                foreach ($data as $key => $value) {
                    if(static::isArrayEmail($array_email, true)) {
                        $value = array_key_first($array_email);
                        $key = reset($array_email);
                    }
                    $addresses = array_merge($addresses, static::getEmailAddressArray(data: $value, name: is_string($key) ? $key : ''));
                }
                break;
            default:
                throw new Exception(vsprintf('Invalid data for making an array of <email => name> with %s', [json_encode($data)]));
                break;
        }
        static::controlEmailArrayList($addresses, true);
        return $addresses;
    }

    public static function controlEmailArrayList(
        array $list,
        bool $exception = false,
    ): bool
    {
        foreach ($list as $email => $name) {
            if(!static::isEmailValid($email) || (!is_string($name) || static::isEmailValid($name))) {
                if($exception) throw new Exception(vsprintf('Emails array list is invalid. Got %s', [json_encode([$email => $name])]));
                return false;
            }
        }
        return true;
    }

    public static function controlEmailAddressList(
        array $list,
        bool $exception = false,
    ): bool
    {
        foreach ($list as $key => $address) {
            if(!($address instanceof Address)) {
                if($exception) throw new Exception(vsprintf('%s array list is invalid. Got %s', [Address::class, json_encode([$key => $address])]));
                return false;
            }
        }
        return true;
    }

    /**
     * Check mail lists and remove doubles or invalid emails
    * @param array ...$mailList
    * @return Opresult
    */
    public static function CheckMailLists(array ...$mailList): Opresult
    {
        // dd($mailList);
        $opresult = new Opresult();
        $opresult->setNullaction(true); // before start, null action is true
        $checkedmails = [];
        $new_mailList = [];
        foreach ($mailList as $key => $mails) {
            $new_mailList_tmp = [];
            static::operate(
                opresult: $opresult,
                mailarray: $mails,
                checkedmails: $checkedmails,
                new_mailList: $new_mailList_tmp
            );
            $new_mailList[$key] = $new_mailList_tmp;
        }
        $opresult->setData($new_mailList);
        // dd($opresult);
        return $opresult;
    }

    public static function operate(Opresult $opresult, array &$mailarray, array &$checkedmails, array &$new_mailList): void
    {
        switch (true) {
            case static::isArrayEmail($mailarray, true):
                $email = array_key_first($mailarray);
                if(!in_array($email, $checkedmails)) {
                    $new_mailList[$email] = $mailarray[$email];
                    $checkedmails[] = $email;
                } else {
                    $opresult->setNullaction(false);
                }
                break;
            case is_array($mailarray):
                foreach($mailarray as $email => $name) {
                    $mailarr = [$email => $name];
                    static::operate(
                        opresult: $opresult,
                        mailarray: $mailarr,
                        checkedmails: $checkedmails,
                        new_mailList: $new_mailList
                    );
                }
                break;
            default:
                // remove BAD format
                $opresult->addActionsFail();
                $opresult->setNullaction(false);
                break;
        }
    }

 
}