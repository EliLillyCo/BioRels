#!/usr/bin/env python
import sys
from rdkit import Chem
from rdkit.Chem import Draw
from PIL import Image
from rdkit.Chem import rdDepictor
from io import BytesIO
import pprint
import argparse

parser = argparse.ArgumentParser(description="first python version")
parser.add_argument('-w', '--width', type=int,help='Width in pixel', default=1000)
parser.add_argument('-ht', '--height',  type=int, default=1000, help='Height in pixel')
parser.add_argument('-s', '--SMILES', type=ascii,required=True,help="SMILES of the compound")
parser.add_argument('-bg', '--background', nargs=4,type=float,help="Background color in RGB float + transparency")
parser.add_argument('-n', '--name',type=ascii,default='',help='Legend')
parser.add_argument('-f', '--font',type=float,default=1,help='Font size (Default 1)')
parser.add_argument('-l', '--line',type=float,default=3,help='Bond line width (Default 3)')

args = parser.parse_args()


def show_mol(d2d,mol,legend='',highlightAtoms=[]):
    d2d.DrawMolecule(mol,legend=legend, highlightAtoms=highlightAtoms)
    d2d.FinishDrawing()
    bio = BytesIO(d2d.GetDrawingText())
    return Image.open(bio)
def show_images(imgs,buffer=5):
    height = 0
    width = 0
    for img in imgs:
        height = max(height,img.height)
        width += img.width
    width += buffer*(len(imgs)-1)
    res = Image.new("RGBA",(width,height))
    x = 0
    for img in imgs:
        res.paste(img,(x,0))
        x += img.width + buffer
    return res



molecule = Chem.MolFromSmiles(args.SMILES.replace('\'', ''))
rdDepictor.Compute2DCoords(molecule)
rdDepictor.StraightenDepiction(molecule)
d2d = Draw.MolDraw2DCairo(args.width,args.height)
dopts=d2d.drawOptions()
if (args.background is not None):
    dopts.setBackgroundColour((args.background[0],args.background[1],args.background[2],args.background[3]))
if (args.font is not None):
	dopts.baseFontSize = args.font
if (args.line is not None):
	dopts.bondLineWidth = args.line


show_mol(d2d,molecule).save(sys.stdout.buffer, "PNG",quality=95)
