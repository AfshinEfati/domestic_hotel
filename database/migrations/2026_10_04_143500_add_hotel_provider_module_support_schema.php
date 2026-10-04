<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('room_calendars', function (Blueprint $table): void {
            $table->unsignedBigInteger('child_daily_rate')
                ->nullable()
                ->after('baby_cot_grs_rate')
                ->comment('Explicit provider child price for this night in IRR; null means calculate from hotel child policy.');
            $table->unsignedBigInteger('infant_daily_rate')
                ->nullable()
                ->after('child_daily_rate')
                ->comment('Explicit provider infant price for this night in IRR; null means calculate from hotel child policy.');
        });

        Schema::table('room_type_provider_maps', function (Blueprint $table): void {
            $table->unsignedSmallInteger('provider_adult_capacity')
                ->nullable()
                ->after('provider_room_type_id')
                ->comment('Adult occupancy capacity declared by this provider for the mapped room.');
            $table->unsignedSmallInteger('provider_child_capacity')
                ->nullable()
                ->after('provider_adult_capacity')
                ->comment('Child occupancy capacity declared by this provider for the mapped room.');
            $table->unsignedSmallInteger('provider_extra_capacity')
                ->nullable()
                ->after('provider_child_capacity')
                ->comment('Extra-bed capacity declared by this provider for the mapped room.');
        });

        Schema::table('facilities', function (Blueprint $table): void {
            $table->string('icon', 1000)
                ->nullable()
                ->after('en_name')
                ->comment('Optional canonical facility icon URL supplied by a provider.');
        });

        Schema::create('accommodation_provider_details', function (Blueprint $table): void {
            $table->id()->comment('Primary key.');
            $table->foreignId('accommodation_provider_map_id')
                ->unique()
                ->comment('Provider-specific accommodation mapping owning these details.')
                ->constrained('accommodation_provider_maps')
                ->cascadeOnDelete();
            $table->text('description')->nullable()->comment('Provider-specific accommodation description.');
            $table->string('provider_url', 1000)->nullable()->comment('Provider-specific accommodation URL or URL key when supplied.');
            $table->boolean('is_marketplace')->nullable()->comment('Provider marketplace flag when supplied by the provider.');
            $table->string('check_in_time', 20)->nullable()->comment('Provider-declared hotel check-in time.');
            $table->string('check_out_time', 20)->nullable()->comment('Provider-declared hotel check-out time.');
            $table->text('cancellation_policy')->nullable()->comment('Provider-declared general cancellation policy text.');
            $table->boolean('foreigners_fee')->nullable()->comment('Whether the provider declares a foreign-guest fee policy for this accommodation.');
            $table->text('free_transfer_policy')->nullable()->comment('Provider-declared free-transfer policy text.');
            $table->json('free_transfers')->nullable()->comment('Provider-declared supported free-transfer types.');
            $table->json('ratings')->nullable()->comment('Provider rating and review summary payload.');
            $table->timestamp('created_at')->nullable()->comment('Row creation timestamp.');
            $table->timestamp('updated_at')->nullable()->comment('Row last update timestamp.');
        });

        Schema::create('accommodation_media', function (Blueprint $table): void {
            $table->id()->comment('Primary key.');
            $table->foreignId('accommodation_provider_map_id')
                ->comment('Provider-specific accommodation mapping owning this media item.')
                ->constrained('accommodation_provider_maps')
                ->cascadeOnDelete();
            $table->foreignId('provider_id')
                ->comment('Provider that supplied this media item.')
                ->constrained('providers')
                ->restrictOnDelete();
            $table->string('type', 20)->comment('Media role such as cover or gallery.');
            $table->char('provider_media_key', 64)->comment('Stable SHA-256 key used to de-duplicate provider media.');
            $table->string('url', 1000)->comment('Original media URL supplied by the provider.');
            $table->string('title', 500)->nullable()->comment('Provider media title.');
            $table->text('description')->nullable()->comment('Provider media description.');
            $table->unsignedInteger('sort_order')->default(0)->comment('Stable display order within the provider media collection.');
            $table->timestamp('created_at')->nullable()->comment('Row creation timestamp.');
            $table->timestamp('updated_at')->nullable()->comment('Row last update timestamp.');

            $table->unique(
                ['accommodation_provider_map_id', 'provider_media_key'],
                'accommodation_media_provider_key_unique'
            );
            $table->index(
                ['accommodation_provider_map_id', 'type'],
                'accommodation_media_map_type_idx'
            );
        });

        Schema::create('accommodation_reviews', function (Blueprint $table): void {
            $table->id()->comment('Primary key.');
            $table->foreignId('accommodation_provider_map_id')
                ->comment('Provider-specific accommodation mapping owning this review.')
                ->constrained('accommodation_provider_maps')
                ->cascadeOnDelete();
            $table->foreignId('provider_id')
                ->comment('Provider that supplied this review.')
                ->constrained('providers')
                ->restrictOnDelete();
            $table->string('provider_review_id', 100)->comment('Provider review identifier.');
            $table->unsignedBigInteger('provider_user_id')->nullable()->comment('Provider user identifier when supplied.');
            $table->string('full_name', 255)->nullable()->comment('Reviewer display name supplied by the provider.');
            $table->text('comment')->nullable()->comment('Review comment text.');
            $table->decimal('comment_risk_level', 10, 4)->nullable()->comment('Provider review comment risk score.');
            $table->boolean('has_ever_booked')->nullable()->comment('Whether the provider marks the reviewer as a previous guest.');
            $table->json('ratings')->nullable()->comment('Normalized provider rating dimensions for this review.');
            $table->boolean('recommended')->nullable()->comment('Provider recommendation flag for this review.');
            $table->string('provider_status', 100)->nullable()->comment('Raw provider review status.');
            $table->timestamp('registered_at')->nullable()->comment('Provider review registration time when available.');
            $table->timestamp('provider_updated_at')->nullable()->comment('Provider review update time when available.');
            $table->timestamp('created_at')->nullable()->comment('Row creation timestamp.');
            $table->timestamp('updated_at')->nullable()->comment('Row last update timestamp.');

            $table->unique(
                ['accommodation_provider_map_id', 'provider_review_id'],
                'accommodation_reviews_provider_review_unique'
            );
        });

        Schema::create('provider_stay_packages', function (Blueprint $table): void {
            $table->id()->comment('Primary key.');
            $table->foreignId('accommodation_provider_map_id')
                ->comment('Provider-specific accommodation mapping owning this stay package.')
                ->constrained('accommodation_provider_maps')
                ->cascadeOnDelete();
            $table->foreignId('provider_id')
                ->comment('Provider that supplied this stay package.')
                ->constrained('providers')
                ->restrictOnDelete();
            $table->foreignId('room_type_provider_map_id')
                ->nullable()
                ->comment('Mapped provider room when the package is room-specific.')
                ->constrained('room_type_provider_maps')
                ->cascadeOnDelete();
            $table->string('provider_room_type_id', 64)->nullable()->comment('Provider room identifier associated with this package.');
            $table->string('title', 500)->nullable()->comment('Provider stay package title.');
            $table->date('check_in')->comment('Inclusive package check-in date.');
            $table->date('check_out')->comment('Exclusive package check-out date.');
            $table->char('package_key', 64)->comment('Stable SHA-256 key for de-duplicating provider stay packages.');
            $table->timestamp('created_at')->nullable()->comment('Row creation timestamp.');
            $table->timestamp('updated_at')->nullable()->comment('Row last update timestamp.');

            $table->unique(
                ['accommodation_provider_map_id', 'package_key'],
                'provider_stay_packages_map_key_unique'
            );
            $table->index(
                ['provider_id', 'check_in', 'check_out'],
                'provider_stay_packages_provider_dates_idx'
            );
        });

        Schema::create('provider_cancellations', function (Blueprint $table): void {
            $table->id()->comment('Primary key.');
            $table->foreignId('provider_id')
                ->comment('Provider handling this cancellation workflow.')
                ->constrained('providers')
                ->restrictOnDelete();
            $table->foreignId('reservation_purchase_id')
                ->nullable()
                ->comment('Local reservation purchase associated with this provider cancellation when known.')
                ->constrained('reservation_purchases')
                ->nullOnDelete();
            $table->string('tracking_code', 100)->comment('Provider booking tracking or reservation code used for cancellation.');
            $table->string('provider_cancellation_id', 100)->nullable()->comment('Provider cancellation request identifier when returned.');
            $table->string('status', 50)->nullable()->comment('Latest raw provider cancellation status.');
            $table->boolean('manual')->nullable()->comment('Whether the provider reports manual cancellation review.');
            $table->unsignedBigInteger('service_fee')->nullable()->comment('Provider cancellation service fee in IRR.');
            $table->unsignedBigInteger('user_penalty')->nullable()->comment('Provider cancellation user penalty in IRR.');
            $table->unsignedSmallInteger('user_penalty_percent')->nullable()->comment('Provider cancellation penalty percentage.');
            $table->unsignedBigInteger('user_penalty_total')->nullable()->comment('Total provider cancellation penalty in IRR.');
            $table->unsignedBigInteger('user_refund_amount')->nullable()->comment('Provider cancellation refund amount in IRR.');
            $table->json('provider_rules')->nullable()->comment('Provider cancellation rule snapshot returned by inquiry.');
            $table->timestamp('requested_at')->nullable()->comment('Time the cancellation request was created with the provider.');
            $table->timestamp('last_inquired_at')->nullable()->comment('Time the provider cancellation status was last checked.');
            $table->timestamp('decided_at')->nullable()->comment('Time the cancellation was accepted or rejected by this service.');
            $table->timestamp('finalized_at')->nullable()->comment('Time a final provider cancellation state was observed.');
            $table->timestamp('created_at')->nullable()->comment('Row creation timestamp.');
            $table->timestamp('updated_at')->nullable()->comment('Row last update timestamp.');

            $table->unique(['provider_id', 'tracking_code'], 'provider_cancellations_tracking_unique');
            $table->index(['provider_id', 'status'], 'provider_cancellations_status_idx');
        });

        Schema::create('provider_price_refresh_states', function (Blueprint $table): void {
            $table->id()->comment('Primary key.');
            $table->foreignId('provider_id')
                ->comment('Provider whose independent refresh state is tracked.')
                ->constrained('providers')
                ->restrictOnDelete();
            $table->foreignId('accommodation_id')
                ->comment('Local accommodation whose provider refresh state is tracked.')
                ->constrained('accommodations')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('schedule_id')->nullable()->comment('Shared SSP schedule identifier used as the refresh policy source.');
            $table->timestamp('next_run_at')->nullable()->comment('Next provider-specific rate and inventory refresh time.');
            $table->timestamp('last_request_at')->nullable()->comment('Most recent provider availability request start time.');
            $table->timestamp('last_success_at')->nullable()->comment('Most recent successful provider HTTP availability response time.');
            $table->timestamp('last_persisted_at')->nullable()->comment('Most recent successful local persistence time.');
            $table->string('last_error', 1000)->nullable()->comment('Most recent provider refresh error summary.');
            $table->timestamp('created_at')->nullable()->comment('Row creation timestamp.');
            $table->timestamp('updated_at')->nullable()->comment('Row last update timestamp.');

            $table->unique(['provider_id', 'accommodation_id'], 'provider_price_refresh_states_unique');
            $table->index(['provider_id', 'next_run_at'], 'provider_price_refresh_states_due_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_price_refresh_states');
        Schema::dropIfExists('provider_cancellations');
        Schema::dropIfExists('provider_stay_packages');
        Schema::dropIfExists('accommodation_reviews');
        Schema::dropIfExists('accommodation_media');
        Schema::dropIfExists('accommodation_provider_details');

        Schema::table('facilities', function (Blueprint $table): void {
            $table->dropColumn('icon');
        });

        Schema::table('room_type_provider_maps', function (Blueprint $table): void {
            $table->dropColumn([
                'provider_adult_capacity',
                'provider_child_capacity',
                'provider_extra_capacity',
            ]);
        });

        Schema::table('room_calendars', function (Blueprint $table): void {
            $table->dropColumn(['child_daily_rate', 'infant_daily_rate']);
        });
    }
};
