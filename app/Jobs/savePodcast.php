<?php


namespace App\Jobs;
use App\PodcastItem;
use App\Podcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class savePodcast implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $podcast;
    public function __construct($podcast)
    {
        $this->podcast = $podcast;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $podcastItemId = $this->podcast['podcastItemId'];
        $user_id       = $this->podcast['user_id'];
        $user_dir      = public_path()."/saved/$user_id/";
        $saved_audio_url = "/saved/$user_id/";

        if (! is_dir($user_dir))
        {
            mkdir($user_dir);
        }

        $item = DB::table('podcast_items')->select('user_id','podcast_id','audio_url')
                                  ->where('user_id', '=', $user_id)
                                  ->where('id', '=', $podcastItemId)
                                  ->first();

        // if item with id exists in DB and is owned by the authenticated user
        if ($item) {

            $podcast = DB::table('podcasts')
                        ->select('id', 'machine_name')
                        ->where('id', '=', $item->podcast_id)->first();

            $machine_name = $podcast->machine_name;

            $itemPath = $user_dir.$machine_name;

            $audio_url = $item->audio_url;

            $audio_url_array = explode("/", $audio_url);         

            $audio_item_path = $itemPath . "/".  end($audio_url_array);

            $saved_audio_url .= $machine_name . "/" . end($audio_url_array);

            \Log::info($audio_item_path);

            if (! is_dir($itemPath))
            {
                mkdir($itemPath);
            }

            if(! copy($audio_url, $audio_item_path))
            {
                echo "Failed to download file";
            }
            
            $podcastItem = PodcastItem::findOrFail($podcastItemId);
            $podcastItem->saved_audio_url = $saved_audio_url;
            $podcastItem->is_saved = 1;
            $podcastItem->save();

        }

    }
}
