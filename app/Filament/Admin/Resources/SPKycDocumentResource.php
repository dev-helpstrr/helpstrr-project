<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SPKycDocumentResource\Pages;
use App\Models\SPKycDocument;
use App\Models\ServiceProvider;
use App\Models\AdminActionLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class SPKycDocumentResource extends Resource
{
    protected static ?string $model = SPKycDocument::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    
    protected static ?string $navigationLabel = 'KYC Documents';
    
    protected static ?string $navigationGroup = 'Service Provider Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('service_provider_id')
                    ->label('Service Provider')
                    ->relationship('serviceProvider', 'name')
                    ->searchable()
                    ->required(),
                    
                Forms\Components\Select::make('document_type')
                    ->options([
                        'aadhaar' => 'Aadhaar Card',
                        'pan' => 'PAN Card',
                        'bank_passbook' => 'Bank Passbook',
                        'police_verification' => 'Police Verification',
                        'photo' => 'Photo',
                        'address_proof' => 'Address Proof'
                    ])
                    ->required(),
                    
                Forms\Components\TextInput::make('document_number')
                    ->label('Document Number'),
                    
                Forms\Components\FileUpload::make('document_url')
                    ->label('Document File')
                    ->directory('kyc-documents')
                    ->acceptedFileTypes(['image/*', 'application/pdf'])
                    ->required(),
                    
                Forms\Components\Select::make('verification_status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'expired' => 'Expired'
                    ])
                    ->default('pending')
                    ->required(),
                    
                Forms\Components\Textarea::make('rejection_reason')
                    ->label('Rejection Reason')
                    ->visible(fn (Forms\Get $get) => $get('verification_status') === 'rejected'),
                    
                Forms\Components\DatePicker::make('expiry_date')
                    ->label('Expiry Date'),
                    
                Forms\Components\KeyValue::make('metadata')
                    ->label('Additional Information')
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('serviceProvider.name')
                    ->label('Service Provider')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('document_type')
                    ->label('Document Type')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'aadhaar' => 'Aadhaar Card',
                        'pan' => 'PAN Card',
                        'bank_passbook' => 'Bank Passbook',
                        'police_verification' => 'Police Verification',
                        'photo' => 'Photo',
                        'address_proof' => 'Address Proof',
                        default => $state
                    })
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('document_number')
                    ->label('Document Number')
                    ->searchable(),
                    
                Tables\Columns\BadgeColumn::make('verification_status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'secondary' => 'expired'
                    ]),
                    
                Tables\Columns\TextColumn::make('expiry_date')
                    ->label('Expiry Date')
                    ->date()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('verifiedBy.name')
                    ->label('Verified By')
                    ->default('Not verified')
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('verification_status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'expired' => 'Expired'
                    ]),
                    
                Tables\Filters\SelectFilter::make('document_type')
                    ->options([
                        'aadhaar' => 'Aadhaar Card',
                        'pan' => 'PAN Card',
                        'bank_passbook' => 'Bank Passbook',
                        'police_verification' => 'Police Verification',
                        'photo' => 'Photo',
                        'address_proof' => 'Address Proof'
                    ]),
                    
                Tables\Filters\Filter::make('expiring_soon')
                    ->query(fn (Builder $query): Builder => $query->where('expiry_date', '<=', now()->addDays(30)))
                    ->label('Expiring Soon (30 days)')
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (SPKycDocument $record): bool => $record->verification_status === 'pending')
                    ->action(function (SPKycDocument $record) {
                        $record->update([
                            'verification_status' => 'approved',
                            'verified_at' => now(),
                            'verified_by' => auth()->id()
                        ]);
                        
                        AdminActionLog::logAction(
                            auth()->id(),
                            'kyc_approval',
                            'SPKycDocument',
                            $record->id,
                            "Approved {$record->document_type} document for {$record->serviceProvider->name}"
                        );
                        
                        Notification::make()
                            ->title('Document Approved')
                            ->success()
                            ->send();
                    }),
                    
                Tables\Actions\Action::make('reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->required()
                    ])
                    ->visible(fn (SPKycDocument $record): bool => $record->verification_status === 'pending')
                    ->action(function (SPKycDocument $record, array $data) {
                        $record->update([
                            'verification_status' => 'rejected',
                            'rejection_reason' => $data['rejection_reason'],
                            'verified_at' => now(),
                            'verified_by' => auth()->id()
                        ]);
                        
                        AdminActionLog::logAction(
                            auth()->id(),
                            'kyc_rejection',
                            'SPKycDocument',
                            $record->id,
                            "Rejected {$record->document_type} document for {$record->serviceProvider->name}: {$data['rejection_reason']}"
                        );
                        
                        Notification::make()
                            ->title('Document Rejected')
                            ->danger()
                            ->send();
                    }),
                    
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListSPKycDocuments::route('/'),
            'create' => Pages\CreateSPKycDocument::route('/create'),
            'edit' => Pages\EditSPKycDocument::route('/{record}/edit'),
            'view' => Pages\ViewSPKycDocument::route('/{record}'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('verification_status', 'pending')->count();
    }
}
