# Anonymous Receipt Printer

A small web page that lets anyone send **anonymous messages and images to a receipt printer via Home Assistant**.

## What is this?

While setting up an ESC/POS thermal printer in Home Assistant for printing daily notifications, reports, and whatever else I could think of, I stumbled across an inspiring project by [Andrew Schmelyun](https://aschmelyun.com/blog/i-invited-strangers-to-message-me-through-a-receipt-printer/).

He invited strangers on the internet to send him messages that would then be printed on a receipt printer. It looked like a fun little social experiment — so I decided to build my own version.

You can also watch the video that inspired the project:

[![I Invited Strangers to Message Me Through a Receipt Printer](https://img.youtube.com/vi/7KtyekivpRM/maxresdefault.jpg)](https://www.youtube.com/watch?v=7KtyekivpRM)

## The Hardware

For this project, I'm using a **RP-T100 receipt printer** from [123inkt.nl](https://www.123inkt.nl/123inkt-huismerk-RP-T100-bonprinter-zwart-22450121C-2421830C-39472730C-39654190C-39659390C-i113052.html).

It's a relatively inexpensive thermal receipt printer with a network connection, which makes it perfect for integrating with Home Assistant.

The printer works with the [ESC/POS Thermal Printer integration for Home Assistant](https://github.com/cognitivegears/ha-escpos-thermal-printer), which I use to send print jobs to the printer over the network.

## Home Assistant

The basic setup looks something like this:

```text
                    Internet
                       │
                       ▼
              Anonymous Web Page
                       │
                       │ API/Webhook
                       ▼
                Home Assistant
                       │
                       │ ESC/POS
                       ▼
                RP-T100 Printer
                       │
                       ▼
                    Receipt
```

The website accepts an anonymous message (and optionally an image), passes it to Home Assistant, and Home Assistant takes care of formatting and printing it.

This also means the printer doesn't need to be directly exposed to the internet.

## Try It

I've currently got a live test version running on my website:

**[Send me an anonymous message](https://www.thedreamer.nl)**

Send something nice, weird, interesting, or completely random.

Just remember that it's a receipt printer — so don't expect a novel to come out of it.

## This Is a Work in Progress

This is very much a **quick-and-dirty side project** that I'm experimenting with.

I'll probably improve the code, interface, security, printing options, and general architecture over time.

For now, it works well enough to be fun.

## Ideas & Suggestions

I'm always open to suggestions!

Some things I'd like to experiment with:

- Better message formatting
- Improve image handling
- QR codes
- Automatic receipt headers and footers
- Better spam protection
- Additional Home Assistant automations
- Maybe some completely unnecessary features, because that's half the fun

If you have an idea, feel free to open an issue or submit a pull request.

## Build Your Own

If you have:

- A network-capable ESC/POS thermal printer
- Home Assistant
- The [ESC/POS Thermal Printer integration](https://github.com/cognitivegears/ha-escpos-thermal-printer)
- A little bit of PHP/web development knowledge

...you should be able to build something similar yourself.

The project is intentionally simple and is meant to be adapted to your own setup.

## Inspiration

A huge part of this project was inspired by **Andrew Schmelyun's** receipt printer experiment.

Check out his original project and write-up:

- [I Invited Strangers to Message Me Through a Receipt Printer](https://aschmelyun.com/blog/i-invited-strangers-to-message-me-through-a-receipt-printer/)
- [Video: I Invited Strangers to Message Me Through a Receipt Printer](https://www.youtube.com/watch?v=7KtyekivpRM)

Thanks for the inspiration, Andrew!

---

### Have fun!

If you build your own version, I'd love to see what you do with it.

**Send a message. Print something. Build something weird.**
