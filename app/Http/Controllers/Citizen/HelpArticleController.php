<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class HelpArticleController extends Controller
{
    private function categories(): Collection
    {
        $categories = collect();

        foreach (require resource_path('help.php') as [$name, $color, $guides]) {
            $category = (object) ['name' => $name, 'slug' => Str::slug($name), 'color' => $color];
            $articles = collect();

            foreach ($guides as [$title, $summary, $popular, $body]) {
                $articles->push((object) [
                    'title' => $title,
                    'slug' => Str::slug($title),
                    'summary' => $summary,
                    'popular' => $popular,
                    'body' => $body,
                    'category' => $category,
                ]);
            }

            $category->articles = $articles->sortBy('title')->values();
            $categories->push($category);
        }

        return $categories;
    }

    public function index(Request $request): View
    {
        $categories = $this->categories();
        $popular = $categories->pluck('articles')->collapse()->where('popular', true)->take(4);
        $search = trim((string) $request->query('q', ''));

        return view('citizen.help.index', compact('categories', 'popular', 'search'));
    }

    public function category(string $category): View
    {
        $category = $this->categories()->firstWhere('slug', $category);
        abort_unless($category, 404);

        return view('citizen.help.category', ['category' => $category, 'articles' => $category->articles]);
    }

    public function show(string $article): View
    {
        $article = $this->categories()->pluck('articles')->collapse()->firstWhere('slug', $article);
        abort_unless($article, 404);

        $related = $article->category->articles->where('slug', '!=', $article->slug)->take(4);

        return view('citizen.help.article', compact('article', 'related'));
    }
}
