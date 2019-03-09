<?php

namespace Spatie\PersonalDataDownload\Tests\Tests\Commands;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\PersonalDataDownload\Tests\TestCase;
use Spatie\PersonalDataDownload\Tests\TestClasses\User;
use Spatie\PersonalDataDownload\Jobs\CreatePersonalDataDownloadJob;

class DeleteOldPersonalDataDownloadsCommandTest extends TestCase
{
    /** @var \Illuminate\Contracts\Filesystem\Filesystem */
    protected $disk;

    public function setUp(): void
    {
        parent::setUp();

        Storage::fake($this->diskName);

        $this->disk = Storage::disk($this->diskName);

        Mail::fake();
    }

    /** @test */
    public function it_will_delete_zips_that_are_older_than_the_configured_amount_of_days()
    {
        $zipFile = $this->createPersonalDataDownload();

        $this->artisan('personal-data-download:clean')->assertExitCode(0);
        $this->assertTrue($this->disk->exists($zipFile));

        $this->progressDays(config('personal-data-download.delete_after_days'));
        $this->artisan('personal-data-download:clean')->assertExitCode(0);
        $this->assertTrue($this->disk->exists($zipFile));

        $this->progressDays(1);
        $this->artisan('personal-data-download:clean')->assertExitCode(0);
        $this->assertFalse($this->disk->exists($zipFile));
    }

    /** @test */
    public function it_will_not_delete_any_other_files()
    {
        $this->disk->put('my-file', 'my contents');

        $this->artisan('personal-data-download:clean')->assertExitCode(0);
        $this->assertTrue($this->disk->exists('my-file'));

        $this->progressDays(100);
        $this->artisan('personal-data-download:clean')->assertExitCode(0);
        $this->assertTrue($this->disk->exists('my-file'));
    }

    protected function createPersonalDataDownload(): string
    {
        $user = factory(User::class)->create();

        dispatch(new CreatePersonalDataDownloadJob($user));

        $allFiles = Storage::disk($this->diskName)->allFiles();

        return Arr::last($allFiles);
    }
}