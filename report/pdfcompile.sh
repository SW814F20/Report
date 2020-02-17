### Removes previous compiled pdf ###
touch main.pdf
rm main.pdf

### Compiles for days ###
pdflatex main.tex
pdflatex main.tex
biber main
pdflatex main.tex

### Cleans up the terminal ###
clear

### Copies compiled pdf with correct name-format ###
cp main.pdf ~/Desktop/$(date +'%Y-%m-%d')-SW709E19.pdf

echo 'Job done!'
