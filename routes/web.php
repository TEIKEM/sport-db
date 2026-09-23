<?php

use App\Http\Controllers\AnalyseController;
use App\Http\Controllers\PostComposerController;
use App\Http\Controllers\PronosticsController;
use App\Http\Controllers\TeamAnalysisController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AnalyseController::class, 'home'])->name('home');
Route::get('/competitions/{league}', [AnalyseController::class, 'competition'])->name('competition');
Route::get('/matchs/{fixture}', [AnalyseController::class, 'fixture'])->name('match');
Route::get('/equipes/{team}', [AnalyseController::class, 'team'])->name('team');
Route::get('/analyse', [TeamAnalysisController::class, 'index'])->name('analyse');
Route::get('/api/teams/search', [TeamAnalysisController::class, 'search'])->name('teams.search');
Route::get('/pronostics', [PronosticsController::class, 'index'])->name('pronostics');
Route::get('/composeur', [PostComposerController::class, 'index'])->name('post-composer');
use App\Http\Controllers\StatsExcelController;

Route::get('/stats-excel', [StatsExcelController::class, 'index'])->name('stats-excel');
Route::get('/stats-excel/export', [StatsExcelController::class, 'export'])->name('stats-excel.export');
Route::post('/stats-excel/import', [StatsExcelController::class, 'import'])->name('stats-excel.import');
