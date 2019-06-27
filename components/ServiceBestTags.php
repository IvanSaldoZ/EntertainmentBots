<?php namespace Cwtuning\Bots\Components;


use Cms\Classes\ComponentBase;
use Cwtuning\Bots\Models\BestTag as BestTagModel;



class ServiceBestTags extends ComponentBase
{

    public $BestTags;



    public function componentDetails()
    {

        return [
            'name' => 'Best tags for latest posts',
            'description' => 'Attach this component to the page where a most popular tags for the latests posts must be showed',
        ];
    }





    public function defineProperties()
    {

        return [];

    }




    /*
     * Before Running the component
     *
     */
    public function onRun()
    {

        $this->BestTags = $this->GetTheMostPopularTags();

    }



    /*
     *
     * Метод для получения самых популярных тэгов (заранее посчитанных сервис-ботом)
     * и занесения в переменные нужных данных
     *
     */
    protected function GetTheMostPopularTags()
    {
        $res = null;

        $BestTags = BestTagModel::orderBy('post_count','desc')->get();
        if (isset($BestTags))
        {
            $res = $BestTags;

        }

        return $res;
    }


}