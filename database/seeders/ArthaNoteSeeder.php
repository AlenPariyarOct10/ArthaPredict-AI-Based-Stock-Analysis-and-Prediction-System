<?php

namespace Database\Seeders;

use App\Models\ArthaNote;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ArthaNoteSeeder extends Seeder
{
    private const POST_COUNT = 100;

    private const IMAGE_SOURCE_DIRECTORY = 'C:\Users\oct10\Downloads\stock_images';

    /**
     * Seed ArthaNotes with their user, like, comment, and reply relationships.
     */
    public function run(): void
    {
        $users = $this->users();
        $imagePaths = $this->copySeedImages();
        $faker = fake();

        for ($index = 1; $index <= self::POST_COUNT; $index++) {
            $author = $users->random();
            $type = $faker->randomElement([
                'insight',
                'market_analysis',
                'educational_note',
            ]);
            $hashtags = $faker->randomElements(
                ['NEPSE', 'stocks', 'investing', 'market', 'trading', 'finance', 'dividend', 'portfolio'],
                $faker->numberBetween(1, 4)
            );
            $publishedAt = Carbon::now()
                ->subDays($faker->numberBetween(0, 44))
                ->subMinutes($faker->numberBetween(0, 1439));

            $note = ArthaNote::create([
                'user_id' => $author->id,
                'type' => $type,
                'title' => $this->titleFor($type, $index),
                'content' => $this->contentFor($type, $hashtags),
                'image_path' => $faker->randomElement($imagePaths),
                'is_pinned' => (bool) $author->is_admin && $faker->boolean(15),
                'hashtags' => array_map('strtolower', $hashtags),
            ]);

            $note->forceFill([
                'created_at' => $publishedAt,
                'updated_at' => $publishedAt,
            ])->saveQuietly();

            $likeUsers = $users->shuffle()->take($faker->numberBetween(0, min(10, $users->count())));
            foreach ($likeUsers as $likeUser) {
                $likedAt = $publishedAt->copy()->addMinutes($faker->numberBetween(1, 720));
                $like = $note->likes()->create([
                    'user_id' => $likeUser->id,
                ]);
                $like->forceFill([
                    'created_at' => $likedAt,
                    'updated_at' => $likedAt,
                ])->saveQuietly();
            }

            $commentCount = $faker->numberBetween(0, 5);
            for ($commentIndex = 0; $commentIndex < $commentCount; $commentIndex++) {
                $commentedAt = $publishedAt->copy()->addMinutes($faker->numberBetween(5, 1440));
                $comment = $note->allComments()->create([
                    'user_id' => $users->random()->id,
                    'body' => $faker->randomElement([
                        'Helpful perspective. I will keep this in mind for my next review.',
                        'The volume trend is especially interesting here.',
                        'Thanks for sharing this analysis with the community.',
                        'I reached a similar conclusion after checking the recent price action.',
                        'This is a useful reminder to manage risk before entering a position.',
                    ]),
                ]);
                $comment->forceFill([
                    'created_at' => $commentedAt,
                    'updated_at' => $commentedAt,
                ])->saveQuietly();

                if ($faker->boolean(35)) {
                    $repliedAt = $commentedAt->copy()->addMinutes($faker->numberBetween(2, 180));
                    $reply = $note->allComments()->create([
                        'user_id' => $users->random()->id,
                        'parent_id' => $comment->id,
                        'body' => $faker->randomElement([
                            'Agreed. Confirmation from the next session would make it stronger.',
                            'That is a good point, especially for short-term traders.',
                            'I will compare this with the sector movement as well.',
                        ]),
                    ]);
                    $reply->forceFill([
                        'created_at' => $repliedAt,
                        'updated_at' => $repliedAt,
                    ])->saveQuietly();
                }
            }
        }

        $this->command?->info(
            sprintf('Seeded %d ArthaNotes using %d stock images.', self::POST_COUNT, count($imagePaths))
        );
    }

    private function users()
    {
        $communityUsers = collect([
            ['Aarav Sharma', 'aarav@arthapredict.test'],
            ['Anisha Karki', 'anisha@arthapredict.test'],
            ['Bikash Rai', 'bikash@arthapredict.test'],
            ['Nisha Thapa', 'nisha@arthapredict.test'],
            ['Prabin Shrestha', 'prabin@arthapredict.test'],
            ['Riya Gurung', 'riya@arthapredict.test'],
            ['Sanjay Adhikari', 'sanjay@arthapredict.test'],
            ['Sushmita Poudel', 'sushmita@arthapredict.test'],
        ]);

        foreach ($communityUsers as [$name, $email]) {
            User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                    'is_admin' => false,
                ]
            );
        }

        return User::query()->get();
    }

    private function copySeedImages(): array
    {
        $sourceDirectory = env('ARTHANOTES_SEED_IMAGE_PATH', self::IMAGE_SOURCE_DIRECTORY);

        if (! File::isDirectory($sourceDirectory)) {
            throw new RuntimeException("ArthaNote image directory does not exist: {$sourceDirectory}");
        }

        $images = collect(File::files($sourceDirectory))
            ->filter(fn ($file) => in_array(
                strtolower($file->getExtension()),
                ['jpg', 'jpeg', 'png', 'webp', 'gif'],
                true
            ))
            ->values();

        if ($images->isEmpty()) {
            throw new RuntimeException("No supported images found in: {$sourceDirectory}");
        }

        $disk = Storage::disk('public');
        $directory = 'arthanotes/seeded';
        $disk->makeDirectory($directory);

        return $images->map(function ($image) use ($disk, $directory) {
            $filename = Str::slug($image->getFilenameWithoutExtension())
                .'.'.strtolower($image->getExtension());
            $path = "{$directory}/{$filename}";

            File::copy($image->getPathname(), $disk->path($path));

            return $path;
        })->all();
    }

    private function titleFor(string $type, int $index): string
    {
        $titles = [
            'insight' => [
                'What today\'s market breadth may be telling us',
                'A patient investor\'s view of the current setup',
                'Three signals worth watching this week',
                'Why risk management matters more than prediction',
            ],
            'market_analysis' => [
                'NEPSE momentum and support zone review',
                'Volume, trend, and sector rotation analysis',
                'Market close review: buyers versus sellers',
                'Technical outlook for the next trading sessions',
            ],
            'educational_note' => [
                'Understanding support and resistance',
                'How to read volume alongside price',
                'Position sizing for long-term survival',
                'A simple checklist before buying a stock',
            ],
        ];

        return fake()->randomElement($titles[$type])." #{$index}";
    }

    private function contentFor(string $type, array $hashtags): string
    {
        $opening = match ($type) {
            'insight' => 'Markets reward preparation more consistently than excitement.',
            'market_analysis' => 'Recent price action shows a market balancing momentum with nearby resistance.',
            'educational_note' => 'A sound process begins with understanding risk, timeframe, and position size.',
        };
        $tagText = collect($hashtags)->map(fn ($tag) => "#{$tag}")->implode(' ');

        return "<p>{$opening}</p>"
            .'<p>Watch the broader trend, trading volume, and sector strength together instead of relying on a single signal. '
            .'Keep invalidation levels clear and avoid committing capital without a plan.</p>'
            ."<p>{$tagText}</p>";
    }
}
