# TODO

## Critical Issues
- [ ] **Bug in `HotelProviderService`**: The service instantiates adapters (e.g., `new GRSAdapter($conf)`) passing an array `$conf`, but the `BaseAdapter` constructor expects a `Provider` model instance (`public function __construct(Provider $provider)`). This will cause a fatal type error.
    - **Fix**: Update `HotelProviderService` to pass the `$provider` object to the adapter constructor.

## Missing Information
- [ ] **SnappTrip API Documentation**: The file `SnappTrip B2B API v1.4.9.pdf` cannot be read by the AI agent.
    - **Action**: Please provide the API documentation in text format (Markdown, JSON, or plain text) or describe the endpoints and data structures required for implementation.
    - **Current Status**: A skeleton `SnappTripAdapter` has been created, but the implementation details (endpoints, request/response mapping) are missing.

## Refactoring & Improvements
- [ ] **Standardization**: Ensure all adapters follow the same error handling and logging patterns.
- [ ] **Type Hinting**: Add strict type hints to all adapter methods.
- [ ] **Testing**: Add unit tests for the new `SnappTripAdapter` once implemented.
