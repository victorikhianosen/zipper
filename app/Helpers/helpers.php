<?php

if (! function_exists('getAirlineMeta')) {
    /**
     * @return array<string, mixed>
     */
    function getAirlineMeta(string $id): array
    {
        $data = @file_get_contents(storage_path('airlines.json')); //@phpstan-ignore-next-line
        $info = collect(json_decode($data))->filter(function ($item) use ($id) {
            return $item->id == strtoupper($id);
        })
            ->toArray();
        $info = array_values($info)[0] ?? null;

        return [
            'id' => $info?->id,
            'name' => $info?->name,
            'logo' => $info?->logo,
        ];
    }
}

if (! function_exists('getRandomReference')) {

    function getRandomReference(string $prefix): string
    {
        return strtoupper($prefix).time().mt_rand(1000, 9999);
    }
}
