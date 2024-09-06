<?php
namespace Aequation\LaboBundle\Service\Tools;

use Aequation\LaboBundle\Service\Base\BaseService;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;

class Times extends BaseService
{

    public const ORIGIN_TIME = '1970-01-01';

    public static function getMicrotimeid() {
		return preg_replace('/^\d\.(\d+)\s(\d+)$/', "$2$1", microtime());
	}

    /** ***********************************************************************************
     * DATE/TIME
     *************************************************************************************/

    public static function getCurrentYear(
        ?string $modifiyer = null,
    ): string
    {
        $date = new DateTime();
        if(!empty($modifiyer)) $date->modify($modifiyer);
        return $date->format('Y');
    }

    public static function getCurrentDate(
        string $pattern = 'd-m-Y',
        ?string $modifiyer = null,
    ): string
    {
        $date = new DateTime();
        if(!empty($modifiyer)) $date->modify($modifiyer);
        return $date->format($pattern);
    }

    public static function getDTTime(string $time = '00:00'): DateTimeInterface
    {
        return new DateTimeImmutable(static::ORIGIN_TIME.' '.$time);
    }

    /**
     * Transform minutes in hours + minutes rest (string)
     * @see https://www.php.net/manual/fr/class.dateinterval.php
     * @param integer|string $mins
     * @param string|null $format
     * @return string
     */
    public static function min_to_hours(
        int|string $mins,
        string $format = null
    ): string
    {
        if(!is_string($mins)) {
            $hours = intdiv($mins, 60);
            $rest = $mins % 60;
            $mins = $hours > 0
                ? $hours.' hours '.$rest.' minutes'
                : $rest.' minutes';
        }
        $tl = DateInterval::createFromDateString($mins);
        /** @see https://www.php.net/manual/fr/dateinterval.format.php */
        $format ??= '%H:%I';
        return $tl->format($format);
    }

    public static function toDateTimeImmut(DateTimeInterface|string &$when): void
    {
        if(!($when instanceof DateTimeInterface)) {
            $when = new DateTimeImmutable($when ?? 'NOW');
        }
    }

	public static function getPreviousDayOfMonth($date = null, $day = "monday") {
		// if($date instanceOf DateTimeImmutable) $date = clone $date; // prevent modifiying original DateTimeImmutable object
		if(is_string($date)) $date = new DateTimeImmutable($date);
		if(!($date instanceOf DateTimeImmutable)) $date = new DateTimeImmutable($date);
		// set first day of month
		$date = new DateTimeImmutable($date->format('Y-m').'-01 00:00:00');
		// $date->modify('first day of month');
		$date->modify($day.' this week');
		return $date;
	}

	public static function pickDate(DateTimeImmutable $toModify, DateTimeImmutable $takeIn) {
		$toModify->setDate(intval($takeIn->format('Y')), intval($takeIn->format('m')), intval($takeIn->format('d')));
		// return $toModify;
	}

	public static function pickTime(DateTimeImmutable $toModify, DateTimeImmutable $takeIn) {
		$toModify->setTime(intval($takeIn->format('H')), intval($takeIn->format('i')), intval($takeIn->format('s')));
		// return $toModify;
	}

	/*** Chrnonometer */

	public static function getTimeLenght(DateTimeImmutable $start, DateTimeImmutable $end = null) {
		$end ??= new DateTimeImmutable();
		$diff = abs($end->getTimestamp() - $start->getTimestamp());
		$hours = floor($diff / 3600);
		$minutes = floor(($diff - $hours * 3600) / 60);
		$seconds = floor($diff - $hours * 3600 - $minutes * 60);
		$hours = $hours < 10 ? '0'.$hours : ''.$hours;
		$minutes = $minutes < 10 ? '0'.$minutes : ''.$minutes;
		$seconds = $seconds < 10 ? '0'.$seconds : ''.$seconds;
		return sprintf("%s:%s:%s", $hours, $minutes, $seconds);
	}

	public static function getStartChrono() {
		return hrtime();
	}

	public static function getEndChrono($start) {
		$end = hrtime();
		$sec = $end[0] - $start[0];
		$ms = abs($end[1] - $start[1]);
		return floatval($sec.'.'.$ms);
	}

    public static function getTimezoneChoices(): array
    {
        return array_combine(timezone_identifiers_list(), timezone_identifiers_list());
    }

}