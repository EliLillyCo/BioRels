#include <ctime>
#include <sstream>
#include <unistd.h>
#include <iomanip>
#include "headers/parser/writerPDB.h"
#include "headers/molecule/macromole.h"
#include"headers/statics/intertypes.h"
#include "headers/molecule/mmresidue_utils.h"
#undef NDEBUG /// Active assertion in release
using namespace protspace;
using namespace std;


WritePDB::WritePDB():WriterBase()
{

}


WritePDB::WritePDB(const std::string& path, const bool& onlySelected):
    WriterBase(path,onlySelected)
{

}

void WritePDB::outputHeader(const MacroMole& molecule)
{
    mOfs << std::left << setw(6)<<"HEADER"
         << " "
         << std::left << setw(39)<< molecule.getName()<<"\n";

}



void WritePDB::outputAtom(const MacroMole& mole)
try{
//         1         2         3         4         5         6         7
//12345678901234567890123456789012345678901234567890123456789012345678901234567890
//ATOM    149 HA3  GLY A  52      15.013  -8.796   7.850      36.93            H
    for (size_t iAtm=0;iAtm < mAtomToConsider.size();++iAtm)
    {
        const MMAtom& atom = mole.getAtom(mAtomToConsider.at(iAtm));
        const MMResidue& residue =atom.getResidue();
        mOfs << std::left << setw(6)<< "ATOM"
             << std::right << setw(5) << (iAtm+1) << " "
             << std::left << setw(4) << atom.getName()
             << " " // Alternate location editor
             << std::left << setw(3) << ((residue.getName().length()<3)?residue.getName():residue.getName().substr(0,3)) << " "
             << std::left << setw(1) << residue.getChainName()
             << std::right << setw(4) << residue.getFID()
             << " " // Code for insertion of MMResiduees
             << "   "
             << std::right << setw(8) <<std::fixed<<std::setprecision(3)<<atom.pos().x()
             << std::right << setw(8) <<std::fixed<<std::setprecision(3)<<atom.pos().y()
             << std::right << setw(8) <<std::fixed<<std::setprecision(3)<<atom.pos().z()
             << std::left << setw(6) <<std::fixed<<std::setprecision(2)
             <<"      "
             << std::left << setw(6) <<std::fixed<<std::setprecision(2)<<atom.getBFactor()
             <<"          "
             << std::right << setw(2) <<atom.getElement();
        switch (atom.getFormalCharge())
        {
        case -3: mOfs<<"3-";break;
        case -2: mOfs <<"2-";break;
        case -1: mOfs <<"1-";break;
        case 1: mOfs <<"1+";break;
        case 2: mOfs <<"2+";break;
            case 3: mOfs <<"3+";break;
        case 0:break;
        default:
            throw_line("470101","WritePDB::outputAtom","Unrecognized formal charge\n"+atom.toString());
        }
        mOfs << "\n";
    }
}catch(ProtExcept &e)
{
    /// ATOM and its residue must exists
    assert(e.getId()!="030401" && e.getId()!="310701");
     e.addHierarchy("WritePDB::outputAtom");
     throw;
}

void WritePDB::save(const MacroMole& mole)
{
    if (!mOfs.is_open())open();
    selectObjects(mole);
    outputHeader(mole);
    outputAtom(mole);
    if (!mBondToConsider.empty())outputBond(mole);
    mOfs<<"END\n";

}

void WritePDB::outputBond(const MacroMole& mole)
try{
    size_t atmpos1,atmpos2;
    ostringstream bonds;
    for (size_t iAtm=0;iAtm < mAtomToConsider.size();++iAtm)
    {
        const MMAtom& atom = mole.getAtom(mAtomToConsider.at(iAtm));
        if (!getAtomPos(atom.getMID(),atmpos1))continue;
        bonds.str("");

        for (size_t iBd=0; iBd < atom.numBonds(); ++iBd)
        {
            MMAtom& atom2=atom.getAtom(iBd);
            if (!getAtomPos(atom2.getMID(),atmpos2))continue;
            if (atmpos2< atmpos1)continue;
            bonds << std::right<<setw(5)<<(atmpos2+1);
        }
        if (bonds.str().empty()) continue;
        mOfs << std::left << setw(6)<<"CONECT"
             << std::right << setw(5)<<(atmpos1+1)
             << bonds.str()<<"\n";

    }

}catch(ProtExcept &e)
{
     assert(e.getId()!="030401");
    e.addHierarchy("WriterPDB::outputBond");
    throw;
}
