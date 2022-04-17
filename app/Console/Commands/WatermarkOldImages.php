<?php

namespace App\Console\Commands;

use Image;
use Config;

use App\Models\Character\Character;
use App\Models\Character\CharacterImage;
use Illuminate\Console\Command;

class WatermarkOldImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'watermark-old-images';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retroactively watermarks character images.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('************************');
        $this->info('* WATERMARK OLD IMAGES *');
        $this->info('************************'."\n");

        $this->info('This command can take a while to run. It will also overlay the watermark on *every* image, regardless if the watermark has previously been applied.');
        if($this->confirm('Are you sure you want to continue?')) {
            // check if user wants to run it on all images or just active images
            $this->info('This command will only run on currently visible characters.');
            if($this->confirm('Do you want to watermark all images or just active ones? Yes = All images.')) {
                $this->info('Watermarking all images...');
                $images = CharacterImage::all();
            } else {
                $this->info('Watermarking active images...');
                $characters = Character::myo(0)->visible()->get();
                $images = CharacterImage::whereIn('character_id', $characters->pluck('character_image_id'))->get();
            }
            $this->info('Found '.$images->count().' images to watermark.');
            $this->line('Beginning watermarking...');

            foreach($images as $image) {
                // get the image
                $img = Image::make('public/' . $image->imageDirectory . '/' . $image->imageFileName);
                $watermark = Image::make('public/images/watermark.png');
                $img->insert($watermark, 'center');

                $img->save('public/' . $image->imageDirectory . '/' . $image->imageFileName, 100, Config::get('lorekeeper.settings.masterlist_image_format'));
            }

            $this->info('Watermarking complete.');
        }
        else $this->line('Bye.');
    }
}
