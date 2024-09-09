#include <math.h>
#include <memory>
#include "headers/math/grid.h"
#include "headers/molecule/macromole.h"
#include "headers/statics/intertypes.h"
#include "headers/proc/volsurf.h"
#include "headers/math/grid_utils.h"
#include "headers/parser/writerMOL2.h"\

double protspace::getOverlap(MacroMole& pMole1,
                             MacroMole& pMole2,
                             const metric& pMetric)
{

    Grid pGrid(0.3,2);
    pGrid.considerMolecule(pMole1);
    pGrid.considerMolecule(pMole2);
    pGrid.createGrid();
    pGrid.perceiveAdjacentBox();

    return getOverlap(pGrid,pMole1,pMole2,pMetric);
}


///
/// \brief protspace::getVolume
/// \param mole
/// \param step
/// \param pFile
/// \return
///
///  A quick assessment has shown a huge impact of the step in the result:
/// Step    Volume
/// 1       336
/// 0.95	338.663
/// 0.90	335.34
/// 0.85	322.416
/// 0.80	316.928
/// 0.75	309.656
/// 0.70	297.038
/// 0.65	287.807
/// 0.60	280.8
/// 0.55	275.85
/// 0.50	269.25
/// 0.45	258.43
/// 0.40	252.736
/// 0.35	245.674
/// 0.30	238.167
/// 0.25	231.516
/// 0.20	223.936
/// 0.15	217.542
///
double protspace::getVolume(MacroMole& mole,const double& step,const std::string& pFile)
{
    /// For export file:
    bool wFile(!pFile.empty());
    protspace::MacroMole* mole2=nullptr;
    if (wFile)mole2=new protspace::MacroMole;
    std::unique_ptr<MacroMole> free_results(mole2);

    ///Creating a grid with a box_length of step Angstroems (preferably below 1)
    /// with a margin of 1.5 (This is to accomodate any Hydrogen)
    Grid g(step,1.5);
    double volume=0;

    /// Creating the grid around the molecule
    g.considerMolecule(mole);
    g.createGrid();
    /// This is needed to consider box that are within an atom sphere
    /// but not being at the center of the atom:
    g.perceiveAdjacentBox();

    ///Now we simply count how many box have an atom (or are in an atom)
    for(int i=0;i<g.getNumBoxes();++i)
    {
        if (g.getBox(i).numAtom()==0&&g.getBox(i).numCloseAtom()==0)continue;
        ++volume;
        if (wFile)
            mole2->addAtom(mole2->getTempResidue(),
                           g.getBox(i).getOrigPos(),
                           "F"+std::to_string(i),"F","F");
    }
    /// We multiply the count by the volume of a cube, i.e. step^3
    volume*=step*step*step;
    if (wFile)
    {
        protspace::WriteMOL2 mw(pFile);
        mw.save(*mole2);}
    return volume;
}


void protspace::getFiboSphere( std::vector<Coords>& list,const int& sample)
{
    const int rnd=1;
    const double offset=2.0/(double)sample;
    const double increment=M_PI*(3-sqrt(5.0));
    Coords coo;
    double r;
    double phi;
    for (int i=0;i<sample;++i)
    {
        coo.setY(((i*offset)-1)+(offset/2));
        r=sqrt(1-pow(coo.y(),2));
        phi=((i+rnd)%sample)*increment;
        coo.setX(cos(phi)*r);
        coo.setZ(sin(phi)*r);
        list.push_back(coo);
    }
}


void protspace::genSurface(protspace::MacroMole& mole, protspace::MacroMole& surf,
                           const size_t& precision)
{
    std::vector<protspace::Coords> list;
    std::vector<size_t> order;
    protspace::Coords newPos;
    /// Getting a list of dots homogeneously dispersed on a sphere of 1 Angstroem
    protspace::getFiboSphere(list,precision);

    /// Scanning all atoms of the molecule
    for(size_t iAtm=0;iAtm < mole.numAtoms();++iAtm)
    {
        const protspace::MMAtom& atom = mole.getAtom(iAtm);
        if (atom.isHydrogen()){order.push_back(-1);continue;}
        for(const protspace::Coords& coo:list)
        {
            /// Getting position of the dot
            newPos=atom.pos()+coo*atom.getvdWRadius();
            bool fail=false;
            /// Here we only want dots at the surface of the molecule
            /// so we don't want any dots that exists at the overlap of two atoms
            for(size_t iAtm2=0;iAtm2< mole.numAtoms();++iAtm2)
            {
                if (iAtm2==iAtm)continue;
                const protspace::MMAtom& atom2 = mole.getAtom(iAtm2);
                if (atom2.isHydrogen())continue;
                if (atom2.pos().distance(newPos,atom2.getvdWRadius()+0.02)< atom2.getvdWRadius()+0.01)
                {
                    fail=true;break;
                }
            }
            if (fail)continue;
            surf.addAtom(surf.getTempResidue(),newPos,atom.getName()+std::to_string(atom.getMID()),"C.3","C").setFID(atom.getMID());
        }
        order.push_back(surf.numAtoms());
    }

    /// Here we remove all dots coming from different molecules
    /// that are closer than 0.2 Angstroems
    /// This is to avoid having dots being in the "canyon" formed by
    /// the partial overlap of two spheres that is not accessible by any
    /// other atom from another molecule.
    std::vector<MMAtom*> toDel;
    for(size_t iA=0;iA<surf.numAtoms();++iA)
    {
        const protspace::MMAtom& atom1= surf.getAtom(iA);
        for(size_t jA=iA+1;jA<surf.numAtoms();++jA)
        {
            const protspace::MMAtom& atom2 = surf.getAtom(jA);
            if (atom1.getFID()==atom2.getFID())continue;
            if (atom1.pos().distance(atom2.pos(),0.21)<=0.2)
            {

                toDel.push_back(&surf.getAtom(iA));
                toDel.push_back(&surf.getAtom(jA));
            }
        }


    }
    std::sort(toDel.begin(),toDel.end());
    toDel.erase(std::unique(toDel.begin(),toDel.end()),toDel.end());
    for(MMAtom* at:toDel)surf.delAtom(*at);
}
