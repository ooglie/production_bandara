<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

final class FinancialYearStoragePath
{
    public static function invoice(
        string $filename,
        CarbonInterface|string $date,
        string|bool $customerType
    ): string {
        return self::build($date, 'Invoices', $customerType, $filename);
    }

    // public static function order(
    //     string $filename,
    //     CarbonInterface|string $date,
    //     string|bool $customerType
    // ): string {
    //     return self::build($date, 'Orders', $customerType, $filename);
    // }

    public static function build(
        CarbonInterface|string $date,
        string $bucket,
        string|bool $customerType,
        string $filename
    ): string {
        $date = self::asIndianDate($date);

        $financialYearStart = $date->month >= 4
            ? $date->year
            : $date->year - 1;

        $financialYearEnd = $financialYearStart + 1;

        $financialYearFolder = sprintf('%d-%d', $financialYearStart, $financialYearEnd);
        $monthFolder = $date->format('M'); // Apr, May, Jun ... Mar
        $customerFolder = self::normalizeCustomerType($customerType);

        return sprintf(
            '%s/%s/%s/%s/%s',
            $financialYearFolder,
            $monthFolder,
            trim($bucket, '/'),
            $customerFolder,
            ltrim($filename, '/')
        );
    }

    private static function normalizeCustomerType(string|bool $customerType): string
    {
        if (is_bool($customerType)) {
            return $customerType ? 'B2B' : 'B2C';
        }

        $value = strtoupper(trim($customerType));

        if (in_array($value, ['B2B', 'B2C'], true)) {
            return $value;
        }

        throw new InvalidArgumentException('Customer type must be B2B or B2C.');
    }

    private static function asIndianDate(CarbonInterface|string $date): CarbonInterface
    {
        if ($date instanceof CarbonInterface) {
            return $date->copy()->setTimezone('Asia/Kolkata');
        }

        return Carbon::parse($date, 'Asia/Kolkata')->setTimezone('Asia/Kolkata');
    }
}