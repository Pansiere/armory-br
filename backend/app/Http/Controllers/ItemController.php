<?php

namespace App\Http\Controllers;

use App\Enums\EquipmentSlot;
use App\Enums\GemColor;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class ItemController extends Controller
{
    public function search(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
            'slot' => ['required_without:gem_color', 'nullable', new Enum(EquipmentSlot::class)],
            'gem_color' => ['required_without:slot', 'nullable', 'integer', Rule::in([1, 2, 4, 8, 14])],
        ]);

        $words = array_filter(preg_split(
            '/\s+/',
            trim(preg_replace('/[+\-<>()~*"@]+/', ' ', $validated['q'])),
        ) ?: []);

        if ($words === []) {
            return ItemResource::collection(collect());
        }

        if (isset($validated['gem_color'])) {
            $query = Item::query()->whereNotNull('gem_color');
            $socketColor = (int) $validated['gem_color'];

            if ($socketColor === GemColor::Meta->value) {
                $query->where('gem_color', GemColor::Meta->value);
            } else {
                $query->where('gem_color', '!=', GemColor::Meta->value)
                    ->whereRaw('(gem_color & ?) != 0', [$socketColor]);
            }
        } else {
            $slot = EquipmentSlot::from($validated['slot']);
            $acceptedSlots = array_map(
                fn ($inventorySlot) => $inventorySlot->value,
                $slot->acceptedInventorySlots(),
            );

            $query = Item::query()->whereIn('slot', $acceptedSlots);
        }

        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            $booleanQuery = implode(' ', array_map(fn (string $word) => '+'.$word.'*', $words));
            $query->whereRaw('MATCH(name) AGAINST(? IN BOOLEAN MODE)', [$booleanQuery]);
        } else {
            foreach ($words as $word) {
                $query->where('name', 'like', '%'.$word.'%');
            }
        }

        $items = $query->orderBy('name')->limit(20)->get();

        return ItemResource::collection($items);
    }
}
