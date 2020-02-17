### CD into report folder
cd report
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

echo 'Job done!'
