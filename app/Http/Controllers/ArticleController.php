<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ArticleController extends Controller
{
    /**
     * @var string
     */
    private $redirectRoute = 'home';

    /**
     * @var array
     */
    private $validationRules = [
        'title' => ['required'],
        'content' => ['required'],
        'published_at' => ['nullable', 'date_format:m/d/Y H:i'],
    ];

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth', ['except' => ['index', 'show']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $articles = Article::published()->orderBy('published_at', 'desc');

        if (auth()->check()) {
            $articles = Article::orderBy('published_at', 'desc');
        }

        $articles = $articles->paginate(env('ARTICLES_PER_PAGE'));

        return view('article.index', compact('articles'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $article = new Article();
        $categories = $this->getCategories();

        return view('article.create', compact('article', 'categories'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate($this->getValidationRulesWithNameUniqueness());

        $category = Category::findOrFail($request->get('category_id'));
        $article = new Article($request->all());
        $category->articles()->save($article);

        flash(__('article.create.success'))->success();

        return redirect()->route('articles.show', [$article]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Article  $article
     * @return \Illuminate\Http\Response
     */
    public function show(Article $article)
    {
        if (!$article->isPublished() && auth()->guest()) {
            abort(403);
        }

        return view('article.show', compact('article'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Article  $article
     * @return \Illuminate\Http\Response
     */
    public function edit(Article $article)
    {
        $categories = $this->getCategories();

        return view('article.edit', compact('article', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Article  $article
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Article $article)
    {
        $request->validate($this->getValidationRulesWithNameUniqueness($article));

        $article->fill($request->all());
        $article->save();

        flash(__('article.edit.success'))->success();

        return redirect()->route('articles.show', [$article]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Article  $article
     * @return \Illuminate\Http\Response
     */
    public function destroy(Article $article)
    {
        $article->delete();

        flash(__('article.destroy.success'))->success();

        return redirect()->route($this->redirectRoute);
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function preview(Request $request)
    {
        $content = parseMarkdown($request->get('content'));

        return response()->json($content);
    }

    private function getValidationRulesWithNameUniqueness(Article $article = null)
    {
        $nameUniquessRule = Rule::unique('articles', 'name');

        if ($article != null) {
            $nameUniquessRule = $nameUniquessRule->ignore($article->id);
        }

        $validationRules = $this->validationRules;
        $validationRules['name'][] = $nameUniquessRule;

        return $validationRules;
    }

    private function getCategories()
    {
        return Category::orderBy('title')->pluck('title', 'id')->toArray();
    }
}
