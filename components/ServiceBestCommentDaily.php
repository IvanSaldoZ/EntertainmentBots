<?php namespace Cwtuning\Bots\Components;


use Cms\Classes\ComponentBase;
use Cwtuning\Bots\Models\BestCommentDaily as BestCommentDailyModel;


class ServiceBestCommentDaily extends ComponentBase
{

    public $BestComment;



    public function componentDetails()
    {

        return [
            'name' => 'Best comment Daily',
            'description' => 'Attach this component to the page where a most popular comment in the day must be showed',
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

        $this->BestComment = $this->GetTheMostPopularComment();

    }



    /*
     *
     * Метод для получения самого популярного коммента и занесения в переменные нужных данных
     *
     */
    protected function GetTheMostPopularComment()
    {
        $res = null;

        $BestCommentDaily = BestCommentDailyModel::orderBy('id','desc')->get()->first();
        if (isset($BestCommentDaily))
        {

            $res = $BestCommentDaily;

        }

        return $res;
    }


}