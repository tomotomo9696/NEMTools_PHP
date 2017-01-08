# NEMTools_PHP

## What's this?
このツール群を使用することで、PHPでのNEMを使用した開発が容易になります。

By using this tool, development using NEM by PHP becomes easy.

似た機能の一部を持つものに、 [NEM-Api-Library](https://github.com/namuyan/NEM-Api-Library) があります。

## How to use

1. まずこのリポジトリを clone します。

  Clone this repository.

 `git clone https://github.com/tomotomo9696/NEMTools_PHP`

2. ライセンス不明のライブラリを使用しているため、これを手動で clone します。

 This tool uses a license unknown library. Therefore, clone that library manually.

 `cd salt`

 `git clone https://github.com/devi/Salt`

3. あとは NEMToolsLoadAll.php を require_once します。

 Please write `require_once("{path}/NEMToolsLoadAll.php");` in your project.

 ```
 <?php
 require_once("./NEMToolsLoadAll.php");
 ```

詳しい使い方は、*example/example.php* をご覧ください。

For detailed usage, please see *example/example.php*.
