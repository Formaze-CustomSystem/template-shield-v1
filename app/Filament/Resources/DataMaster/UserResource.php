<?php

namespace App\Filament\Resources\DataMaster;

use App\Filament\Resources\DataMaster\UserResource\Pages;
use App\Filament\Resources\DataMaster\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Data Master';

    protected static ?string $navigationLabel = 'User';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (!auth()->user()?->hasRole('super_admin')) {
            $query->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'super_admin');
            });
        }

        return $query;
    }

    public static function getNavigationBadge(): ?string
    {
        $query = User::query();

        // If the current user is not a super_admin, exclude super_admin users
        if (!auth()->user()?->hasRole('super_admin')) {
            $query->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'super_admin');
            });
        }

        return (string) $query->count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('User Information')
                    ->description('Fill in the basic information of the user.')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label('Full Name')
                                ->required()
                                ->maxLength(255)
                                ->autofocus()
                                ->placeholder('Enter full name'),

                            TextInput::make('email')
                                ->label('Email Address')
                                ->email()
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->placeholder('Enter email address'),
                        ]),

                        Grid::make(2)->schema([
                            TextInput::make('password')
                                ->label('Password')
                                ->password()
                                ->dehydrated(fn($state) => filled($state))
                                ->required(fn($livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord)
                                ->maxLength(255)
                                ->placeholder('Set a password'),

                            Toggle::make('is_active')
                                ->label('Active')
                                ->default(true)
                                ->hidden(), // Optional: remove from UI
                        ]),
                    ])
                    ->columns(1)
                    ->collapsible()
                    ->icon('heroicon-m-user'),

                Section::make('User Roles')
                    ->description('Assign one or more roles to the user.')
                    ->schema([
                        Select::make('roles')
                            ->label('Roles')
                            ->multiple()
                            ->relationship('roles', 'name') // This syncs the relationship
                            ->options(function () {
                                $query = Role::query();

                                // Only super_admins can assign the super_admin role
                                if (!auth()->user()?->hasRole('super_admin')) {
                                    $query->where('name', '!=', 'super_admin');
                                }

                                return $query->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->placeholder('Select user roles')
                            ->hint('You can assign multiple roles')
                    ])
                    ->columns(1)
                    ->icon('heroicon-m-shield-check')
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Full Name')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Email copied')
                    ->copyMessageDuration(1500),

                TextColumn::make('roles.name')
                    ->label('Roles')
                    ->sortable()
                    ->formatStateUsing(fn($state) => collect($state)->join(', '))
                    ->badge()
                    ->color('primary'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->date('M d, Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->label('Filter by Role')
                    ->multiple()
                    ->relationship(
                        name: 'roles',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn($query) => $query->when(
                            !auth()->user()?->hasRole('super_admin'),
                            fn($q) => $q->where('name', '!=', 'super_admin')
                        )
                    )
                    ->searchable()
                    ->preload()
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                // Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->emptyStateHeading('No users found')
            ->emptyStateDescription('You can create a user using the Create User button.');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
