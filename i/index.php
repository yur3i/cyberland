<?php
include("../cyberland.php");
if (isset($_POST["content"])) {
    post("images");
} else {
    get("images");
}
