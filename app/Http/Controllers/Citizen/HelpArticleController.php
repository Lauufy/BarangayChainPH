<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Models\HelpArticle;
use App\Models\HelpCategory;

class HelpArticleController extends Controller
{
    public function category(HelpCategory $category)
    {
        $articles = $category->articles()->published()->orderBy('title')->get();

        return view('citizen.help.category', compact('category', 'articles'));
    }

    public function show(HelpArticle $article)
    {
        abort_unless($article->is_published, 404);

        $article->increment('views');
        $article->load('category');

        $related = HelpArticle::published()
            ->where('help_category_id', $article->help_category_id)
            ->whereKeyNot($article->id)
            ->limit(4)
            ->get();

        return view('citizen.help.article', compact('article', 'related'));
    }
}