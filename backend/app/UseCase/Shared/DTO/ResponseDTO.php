<?php

declare(strict_types=1);

namespace App\UseCase\Shared\DTO;

abstract class ResponseDTO
{
    public function toArray(): array
    {
        $reflection = new \ReflectionClass($this);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED);
        
        $result = [];
        foreach ($properties as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($this);
            
            if ($value instanceof ResponseDTO) {
                $result[$property->getName()] = $value->toArray();
            } elseif (is_array($value)) {
                $result[$property->getName()] = array_map(function ($item) {
                    return $item instanceof ResponseDTO ? $item->toArray() : $item;
                }, $value);
            } else {
                $result[$property->getName()] = $value;
            }
        }
        
        return $result;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}