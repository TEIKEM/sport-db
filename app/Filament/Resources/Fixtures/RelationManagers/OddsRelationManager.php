<?php

namespace App\Filament\Resources\Fixtures\RelationManagers;

use App\Models\Odd;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OddsRelationManager extends RelationManager
{
    protected static string $relationship = 'odds';

    protected static ?string $title = 'Cotes';

    /**
     * Marchés proposés : code du marché => [libellé, [code de la sélection => libellé]].
     * Ces codes sont ceux enregistrés dans la base et affichés dans la page d'analyse.
     */
    private const MARKETS = [
        '1X2' => ['Résultat final (1X2)', [
            'HOME' => 'Domicile',
            'DRAW' => 'Nul',
            'AWAY' => 'Extérieur',
        ]],
        'DC' => ['Double chance', [
            'HOME_DRAW' => 'Domicile ou nul',
            'HOME_AWAY' => 'Domicile ou extérieur',
            'DRAW_AWAY' => 'Nul ou extérieur',
        ]],
        'DNB' => ['Remboursé si match nul', [
            'HOME' => 'Domicile',
            'AWAY' => 'Extérieur',
        ]],
        'OU0.5' => ['Plus / moins de 0.5 but', ['OVER' => 'Plus de 0.5', 'UNDER' => 'Moins de 0.5']],
        'OU1.5' => ['Plus / moins de 1.5 but', ['OVER' => 'Plus de 1.5', 'UNDER' => 'Moins de 1.5']],
        'OU2.5' => ['Plus / moins de 2.5 buts', ['OVER' => 'Plus de 2.5', 'UNDER' => 'Moins de 2.5']],
        'OU3.5' => ['Plus / moins de 3.5 buts', ['OVER' => 'Plus de 3.5', 'UNDER' => 'Moins de 3.5']],
        'OU4.5' => ['Plus / moins de 4.5 buts', ['OVER' => 'Plus de 4.5', 'UNDER' => 'Moins de 4.5']],
        'BTTS' => ['Les deux équipes marquent', ['YES' => 'Oui', 'NO' => 'Non']],
    ];

    /** Formulaire de correction d'une seule cote. */
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('bookmaker')->label('Bookmaker')->required()->maxLength(50),
            TextInput::make('market')->label('Marché')->required()->maxLength(30),
            TextInput::make('selection')->label('Sélection')->required()->maxLength(30),
            TextInput::make('price')->label('Cote')->numeric()->minValue(1)->step(0.001)->required(),
            DateTimePicker::make('recorded_at')->label('Date et heure de la cote')->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('recorded_at', 'desc')
            ->columns([
                TextColumn::make('recorded_at')->label('Date')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('bookmaker')->label('Bookmaker')->searchable(),
                TextColumn::make('market')->label('Marché'),
                TextColumn::make('selection')->label('Sélection'),
                TextColumn::make('price')
                    ->label('Cote')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2)),
            ])
            ->headerActions([
                Action::make('saisir_cotes')
                    ->label('Saisir les cotes')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Cotes du match')
                    ->modalDescription('Remplis seulement les cotes que tu as : les cases vides sont ignorées.')
                    ->modalSubmitActionLabel('Enregistrer')
                    ->modalWidth('4xl')
                    ->schema(fn (): array => self::modalFields())
                    ->action(function (array $data): void {
                        $fixture = $this->getOwnerRecord();
                        $count = 0;

                        foreach (self::MARKETS as $market => [$label, $selections]) {
                            foreach ($selections as $selection => $selectionLabel) {
                                $price = $data[self::fieldName($market, $selection)] ?? null;

                                if ($price === null || $price === '') {
                                    continue;
                                }

                                Odd::create([
                                    'fixture_id' => $fixture->id,
                                    'bookmaker' => $data['bookmaker'],
                                    'market' => $market,
                                    'selection' => $selection,
                                    'price' => $price,
                                    'recorded_at' => $data['recorded_at'],
                                ]);

                                $count++;
                            }
                        }

                        if ($count === 0) {
                            Notification::make()
                                ->title('Aucune cote saisie')
                                ->warning()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title($count . ' cote(s) enregistrée(s)')
                            ->success()
                            ->send();
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    /** Les champs de la fenêtre de saisie : bookmaker, heure, puis un bloc par marché. */
    private static function modalFields(): array
    {
        $fields = [
            Grid::make(2)->schema([
                TextInput::make('bookmaker')
                    ->label('Bookmaker')
                    ->required()
                    ->maxLength(50)
                    ->datalist(['1xBet', 'Bet365', 'Betway', 'Pinnacle', 'Melbet', 'Betclic', 'Unibet']),

                DateTimePicker::make('recorded_at')
                    ->label('Date et heure de ces cotes')
                    ->default(fn () => now())
                    ->seconds(false)
                    ->required(),
            ]),
        ];

        foreach (self::MARKETS as $market => [$label, $selections]) {
            $inputs = [];

            foreach ($selections as $selection => $selectionLabel) {
                $inputs[] = TextInput::make(self::fieldName($market, $selection))
                    ->label($selectionLabel)
                    ->numeric()
                    ->minValue(1)
                    ->step(0.001);
            }

            $fields[] = Fieldset::make($label)
                ->columns(count($inputs))
                ->schema($inputs);
        }

        return $fields;
    }

    /** Nom de champ sans point ni espace (ex. c_OU2_5_OVER). */
    private static function fieldName(string $market, string $selection): string
    {
        return 'c_' . str_replace('.', '_', $market) . '_' . $selection;
    }
}
