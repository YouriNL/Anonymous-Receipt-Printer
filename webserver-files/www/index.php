<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="css/style.css" rel="stylesheet">

    <title>Print something!</title>
</head>

<body>

<div class="bg"></div>
<div class="bg bg2"></div>
<div class="bg bg3"></div>

<div class="container">


    <h1>PING!</h1>

    <p class="intro">
        ***************************************<br>
        THANKS FOR STOPPING BY<br>
        Your message will be sent to my printer<br>
        ***************************************
    </p>

    <div class="tabs">

        <button
            type="button"
            class="tab active"
            data-tab="message"
        >
            Message
        </button>

        <button
            type="button"
            class="tab"
            data-tab="image"
        >
            Image
        </button>

    </div>


    <!-- =====================================================
         MESSAGE
         ===================================================== -->

    <div
        id="messagePanel"
        class="panel active"
    >

        <textarea
            id="message"
            maxlength="1024"
            placeholder="Write your message..."
        ></textarea>

        <div class="counter">
            <span id="messageCount">0</span> / 1024
        </div>

        <div class="buttons">

            <button
                type="button"
                class="action secondary"
                id="clearMessage"
            >
                Clear
            </button>

            <button
                type="button"
                class="action"
                id="printMessage"
            >
                Print message
            </button>

        </div>

    <p class="intro">
    ===========================<br>
    Basic text only! (no emojis, symbols)<br>
    Printer text width is 42 characters<br>
    Share your thoughts & fantasies<br>
    Leave contact details if you like<br>
    ===========================
    </p>

    </div>


    <!-- =====================================================
         IMAGE
         ===================================================== -->

    <div
        id="imagePanel"
        class="panel"
    >

        <div
            class="dropzone"
            id="dropzone"
        >

            <strong>
                Drop an image here
            </strong>

            <span>
                or click to choose a file
            </span>

            <input
                type="file"
                id="imageInput"
                accept="
                    image/png,
                    image/jpeg,
                    image/gif,
                    image/bmp,
                    image/tiff,
                    image/webp
                "
            >

        </div>


        <div
            class="preview"
            id="preview"
        >

            <img
                id="previewImage"
                alt="Image preview"
            >

            <div
                class="filename"
                id="filename"
            ></div>

        </div>


        <div class="buttons">

            <button
                type="button"
                class="action secondary"
                id="clearImage"
            >
                Clear
            </button>

            <button
                type="button"
                class="action"
                id="printImage"
                disabled
            >
                Print image
            </button>

        </div>

    <p class="intro">
    ===========================<br>
    Max image size is 10MB<br>
    Images will not be saved<br>
    I'll except the naughty ones<br>
    ===========================
    </p>

    </div>



    <!-- =====================================================
         STATUS
         ===================================================== -->

    <div
        class="status"
        id="status"
    ></div>

    <div
        class="ticket"
        id="ticket"
    ></div>

    <footer>
        Powered by Home Assistant<br>
        ESC/POS Thermal Printer<br>
        <br>
        <a href="https://github.com/YouriNL/Anonymous-Receipt-Printer" target="_blank" rel="noopener">
          <svg height="32" width="32" viewBox="0 0 16 16" version="1.1" aria-hidden="true">
            <path fill-rule="evenodd" d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"></path>
          </svg>
        </a>
    </footer>

</div>

<script>

/* ==========================================================
   ELEMENTS
   ========================================================== */

const tabs =
    document.querySelectorAll('.tab');

const messagePanel =
    document.getElementById('messagePanel');

const imagePanel =
    document.getElementById('imagePanel');

const message =
    document.getElementById('message');

const messageCount =
    document.getElementById('messageCount');

const dropzone =
    document.getElementById('dropzone');

const imageInput =
    document.getElementById('imageInput');

const preview =
    document.getElementById('preview');

const previewImage =
    document.getElementById('previewImage');

const filename =
    document.getElementById('filename');

const printMessageButton =
    document.getElementById('printMessage');

const printImageButton =
    document.getElementById('printImage');

const clearMessageButton =
    document.getElementById('clearMessage');

const clearImageButton =
    document.getElementById('clearImage');

const status =
    document.getElementById('status');

const ticket =
    document.getElementById('ticket');


let imageData = null;


/* ==========================================================
   TABS
   ========================================================== */

tabs.forEach(tab => {

    tab.addEventListener('click', () => {

        tabs.forEach(item => {
            item.classList.remove('active');
        });

        tab.classList.add('active');

        const selected =
            tab.dataset.tab;


        if (selected === 'message') {

            messagePanel.classList.add('active');
            imagePanel.classList.remove('active');

        } else {

            messagePanel.classList.remove('active');
            imagePanel.classList.add('active');

        }

        clearStatus();

    });

});


/* ==========================================================
   MESSAGE COUNTER
   ========================================================== */

message.addEventListener('input', () => {

    messageCount.textContent =
        message.value.length;

});


/* ==========================================================
   IMAGE SELECT
   ========================================================== */

dropzone.addEventListener('click', () => {

    imageInput.click();

});


imageInput.addEventListener('change', () => {

    if (imageInput.files.length > 0) {

        loadImage(
            imageInput.files[0]
        );

    }

});


/* ==========================================================
   DRAG & DROP
   ========================================================== */

dropzone.addEventListener(
    'dragover',
    event => {

        event.preventDefault();

        dropzone.classList.add(
            'dragover'
        );

    }
);


dropzone.addEventListener(
    'dragleave',
    () => {

        dropzone.classList.remove(
            'dragover'
        );

    }
);


dropzone.addEventListener(
    'drop',
    event => {

        event.preventDefault();

        dropzone.classList.remove(
            'dragover'
        );


        const files =
            event.dataTransfer.files;


        if (files.length > 0) {

            loadImage(files[0]);

        }

    }
);


/* ==========================================================
   LOAD IMAGE
   ========================================================== */

function loadImage(file) {

    if (!file.type.startsWith('image/')) {

        showError(
            'Please select an image file.'
        );

        return;
    }


    /*
     * Maximum browser-side image size:
     * 10 MB.
     */

    if (
        file.size >
        10 * 1024 * 1024
    ) {

        showError(
            'The image is too large. Maximum is 10 MB.'
        );

        return;
    }


    const reader =
        new FileReader();


    reader.onload =
        event => {

            imageData =
                event.target.result;


            previewImage.src =
                imageData;


            filename.textContent =
                file.name +
                ' · ' +
                Math.round(
                    file.size / 1024
                ) +
                ' KB';


            preview.style.display =
                'block';


            printImageButton.disabled =
                false;


            clearStatus();

        };


    reader.readAsDataURL(file);

}


/* ==========================================================
   CLEAR MESSAGE
   ========================================================== */

clearMessageButton.addEventListener(
    'click',
    () => {

        message.value = '';

        messageCount.textContent =
            '0';

        clearStatus();

    }
);


/* ==========================================================
   CLEAR IMAGE
   ========================================================== */

clearImageButton.addEventListener(
    'click',
    () => {

        imageData = null;

        imageInput.value = '';

        preview.style.display =
            'none';

        previewImage.src = '';

        filename.textContent = '';

        printImageButton.disabled =
            true;

        clearStatus();

    }
);


/* ==========================================================
   PRINT MESSAGE
   ========================================================== */

printMessageButton.addEventListener(
    'click',
    async () => {

        const text =
            message.value.trim();


        if (!text) {

            showError(
                'Please enter a message.'
            );

            return;
        }


        await sendToPrinter({

            type: 'text',

            text: text

        });

    }
);


/* ==========================================================
   PRINT IMAGE
   ========================================================== */

printImageButton.addEventListener(
    'click',
    async () => {

        if (!imageData) {

            showError(
                'Please select an image.'
            );

            return;
        }


        await sendToPrinter({

            type: 'image',

            image: imageData

        });

    }
);


/* ==========================================================
   SEND TO PHP
   ========================================================== */

async function sendToPrinter(data) {

    setBusy(true);

    status.className =
        'status';

    status.textContent =
        'Sending to printer...';

    ticket.style.display =
        'none';


    try {

        const response =
            await fetch(
                './print.php',
                {
                    method: 'POST',

                    headers: {
                        'Content-Type':
                            'application/json'
                    },

                    body:
                        JSON.stringify(data)
                }
            );


        let result;

        try {

            result =
                await response.json();

        } catch (e) {

            throw new Error(
                'Invalid response from server.'
            );

        }


        if (
            !response.ok ||
            !result.success
        ) {

            throw new Error(
                result.error ||
                'Unable to print.'
            );

        }


        status.className =
            'status success';

        status.textContent =
            'Sent to printer.';


        if (result.ticket) {

            ticket.textContent =
                '#' + result.ticket;

            ticket.style.display =
                'block';

        }


        /*
         * Clear the submitted content.
         */

        if (data.type === 'text') {

            message.value = '';

            messageCount.textContent =
                '0';

        } else {

            imageData = null;

            imageInput.value = '';

            preview.style.display =
                'none';

            previewImage.src = '';

            filename.textContent = '';

            printImageButton.disabled =
                true;

        }


    } catch (error) {

        showError(
            error.message
        );

    } finally {

        setBusy(false);

    }

}


/* ==========================================================
   BUSY STATE
   ========================================================== */

function setBusy(busy) {

    printMessageButton.disabled =
        busy;

    printImageButton.disabled =
        busy || !imageData;

}


/* ==========================================================
   ERROR
   ========================================================== */

function showError(text) {

    status.className =
        'status error';

    status.textContent =
        text;

}


/* ==========================================================
   CLEAR STATUS
   ========================================================== */

function clearStatus() {

    status.className =
        'status';

    status.textContent =
        '';

    ticket.style.display =
        'none';

}

</script>

</body>
</html>
