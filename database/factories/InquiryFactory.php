<?php

namespace Database\Factories;

use App\Enums\InquiryStatus;
use App\Models\Inquiry;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Inquiry>
 */
class InquiryFactory extends Factory
{
    protected $model = Inquiry::class;

    public function definition(): array
    {
        return [
            'inquiry_number' => 'INQ-'.now()->format('Ymd').'-'.Str::upper(Str::random(7)),
            'name' => fake()->name(),
            'company' => fake()->optional()->company(),
            'phone' => '08'.fake()->numerify('##########'),
            'email' => fake()->optional()->safeEmail(),
            'product_id' => null,
            'product_name' => fake()->randomElement(['Rubber Seal Ring', 'Plug Rubber', 'Mudguard Truk', 'Rubber Outsole']),
            'size' => fake()->randomElement(['ID 50mm', '40 x 50 cm', 'Size 42', 'Diameter 30 mm']),
            'quantity' => fake()->numberBetween(50, 2000),
            'unit' => fake()->randomElement(['Pcs', 'Pasang', 'Set']),
            'variant' => fake()->optional()->randomElement(['Hitam', 'Biru', 'Brown Gum']),
            'notes' => fake()->optional()->sentence(),
            'status' => InquiryStatus::Baru,
        ];
    }

    public function status(InquiryStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
