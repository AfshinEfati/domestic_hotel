# Hotel GDS Architecture Documentation

این پوشه مرجع معماری جاری Hotel GDS است.

## اسناد اصلی

1. [`HOTEL_GDS_ARCHITECTURE.md`](./HOTEL_GDS_ARCHITECTURE.md)  
   نمای فنی سیستم، Provider modules، نرخ و ظرفیت، Shared SSP، mapping، Reservation، Purchase، Cancellation و Queue/Horizon.

2. [`HOTEL_GDS_IMPLEMENTATION_CHECKLIST.md`](./HOTEL_GDS_IMPLEMENTATION_CHECKLIST.md)  
   چک‌لیست زنده بخش‌های انجام‌شده، نیازمند verification، ناقص و پیاده‌سازی‌نشده، همراه با اولویت کارهای بعدی.

## رابطه با `docs/checkpoints`

فایل‌های `docs/checkpoints` تاریخچه تصمیم‌ها و وضعیت در زمان خودشان هستند. آن‌ها حذف نمی‌شوند، اما ممکن است بعضی جزئیاتشان با معماری فعلی تفاوت داشته باشد.

برای تصمیم توسعه جدید:

1. ابتدا کد branch فعلی را بررسی کنید؛
2. سپس این دو سند را به‌عنوان نقشه وضعیت جاری بخوانید؛
3. checkpointهای قدیمی را برای دلیل تصمیم‌ها و تاریخچه استفاده کنید؛
4. اگر معماری یا وضعیت feature تغییر کرد، architecture و checklist را در همان تغییر به‌روزرسانی کنید.
