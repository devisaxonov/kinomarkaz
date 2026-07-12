<?php

return [
    'welcome' => "👋 <b>Assalomu alaykum, {name}!</b>\n\n"
               . "🎬 <b>KinoMarkaz</b> raqamli tizimiga xush kelibsiz.\n"
               . "🔍 Qidirayotgan kinoyingizning maxsus kodini yuboring.\n\n"
               . "<i>💡 Qo'llanma uchun /help tugmasini bosing.</i>",

    'help' => "ℹ️ <b>Qo'llanma:</b>\n\n"
            . "Kino yoki serialni topish uchun botga ushbu kinoning <b>maxsus kodini</b> yuboring.\n"
            . "Masalan: <code>101</code> yoki <code>205</code> kabi.\n\n"
            . "Tizim darhol videoni sizga yuboradi!",

    'must_subscribe' => "⚠️ <b>Obuna talab etiladi!</b>\n"
                      . "━━━━━━━━━━━━━━━━━━\n"
                      . "Botdan to'liq va bepul foydalanish uchun quyidagi homiy kanallarimizga a'zo bo'lishingiz zarur.\n\n"
                      . "<i>👇 Obuna bo'lgach, «✅ Tasdiqlash» tugmasini bosing!</i>",

    'movie_found' => "🎬 <b>Kino topildi!</b>\n"
                   . "━━━━━━━━━━━━━━━━━━\n"
                   . "🎯 <b>Kod:</b> <code>{code}</code>\n"
                   . "🎞 <b>Nomi:</b> {title}\n"
                   . "🌍 <b>Davlat:</b> {country}\n"
                   . "📅 <b>Yili:</b> {year}\n"
                   . "🗣 <b>Tili:</b> {language}\n"
                   . "🎥 <b>Sifati:</b> {quality}\n"
                   . "⏳ <b>Davomiyligi:</b> {duration}\n"
                   . "━━━━━━━━━━━━━━━━━━\n"
                   . "📝 <b>Tavsif:</b> <i>{description}</i>\n\n"
                   . "✨ <b>KinoMarkaz</b> sizga maroqli tomosha tilaydi!",

    'movie_not_found' => "❌ <b>Kechirasiz!</b>\n\n"
                       . "Siz kiritgan <code>{code}</code> kodiga mos kino topilmadi.\n\n"
                       . "🔍 Kodni qayta tekshirib ko'ring yoki boshqa kod kiriting.",

    'admin_wizard_start' => "🚀 <b>Yangi kino yoki serial qo'shish!</b>\n"
                          . "━━━━━━━━━━━━━━━━━━\n"
                          . "Iltimos, yopiq kanaldan videoni shu yerga <b>Forward</b> qiling yoki to'g'ridan-to'g'ri jo'nating.",
    
    'wizard_ask_title' => "🎬 <b>Kino/Serial nomini yozing:</b>\n\n<i>Masalan: Qasoskorlar (2012) yoki Qora ro'yxat 1-fasl 1-qism</i>",
    'wizard_ask_description' => "📝 <b>Tavsifini yozing:</b>\n\n<i>Qisqacha mazmuni...</i>",
    'wizard_ask_genre' => "📂 <b>Janrni kiriting:</b> (masalan: Jangari, Komediya)",
    'wizard_ask_country' => "🌍 <b>Davlatni kiriting:</b>",
    'wizard_ask_year' => "📅 <b>Kino yilini kiriting:</b> (masalan: 2024)",
    'wizard_ask_language' => "🗣 <b>Tilni kiriting:</b>",
    'wizard_ask_quality' => "🎞 <b>Sifatini kiriting:</b> (masalan: 1080p, 720p)",
    'wizard_ask_duration' => "⏳ <b>Davomiyligini kiriting:</b> (masalan: 1 soat 45 daqiqa)",
    'wizard_ask_poster' => "🖼 <b>Poster (rasm) yuboring:</b>\n\n<i>Yoki \"o'tkazish\" deb yozing.</i>",
    'wizard_ask_code' => "🎯 <b>Maxsus kod kiriting (Kino/Serial kodi):</b>\n\n<i>Agar avtomatik generatsiya qilishni istasangiz, \"avto\" deb yozing.</i>",
    'wizard_success' => "✅ <b>Muvaffaqiyatli saqlandi!</b>\n\n🎯 Kod: <b>{code}</b>\n🎞 Nomi: <b>{title}</b>",
    'wizard_cancelled' => "❌ <b>Jarayon bekor qilindi.</b>",
    
    'error_not_forwarded' => "⚠️ Iltimos, yopiq kanaldagi videoni <b>Forward</b> qiling!",
    'error_code_exists' => "⚠️ Ushbu kod band! Boshqa kod yozing yoki 'avto' deng.",
    'error_internal' => "⚙️ <b>Texnik nosozlik!</b>\n\n"
                      . "Tizimda vaqtincha uzilish yuz berdi. Tez orada muammo bartaraf etiladi."
];
