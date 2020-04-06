### CD into report folder
cd report
### Removes previous compiled pdf ###
touch main.pdf
rm main.pdf

### Compiles for days ###
pdflatex -interaction=errorstopmode main.tex
pdflatex -interaction=errorstopmode main.tex
biber main
pdflatex -interaction=errorstopmode main.tex

### Cleans up the terminal ###
clear

echo 'Job done!'
