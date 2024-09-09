#include <ctime>
#include <sstream>
#include <unistd.h>
#include <iomanip>
#include "headers/molecule/macromole.h"
#include "headers/parser/writerSDF.h"
#include "headers/statics/intertypes.h"
#undef NDEBUG /// Active assertion in release


protspace::WriterSDF::WriterSDF():WriterBase(),
    mOutputDate(true),
    mOutputUserID(true)
{

}


protspace::WriterSDF::WriterSDF(const std::string& path, const bool& onlySelected):
    WriterBase(path,onlySelected),
    mOutputDate(true),
    mOutputUserID(true)
{

}

void protspace::WriterSDF::outputHeader(const MacroMole &pMolecule)
{
    if (!mOfs.is_open())
        throw_line("480101",
                   "WriterSDF::outputHeader",
                   "No file opened");

    if (mAtomToConsider.size() >999)
        throw_line("480102",
                   "WriterSDF::outputHeader",
                   "SDF file limited to 999 atoms");
    if (mBondToConsider.size() >999)
        throw_line("480103",
                   "WriterSDF::outputHeader",
                   "SDF file limited to 999 bonds");


    mOfs  << ((pMolecule.getName().length()==0)?"unknown":pMolecule.getName())<<"\n";
    mOfs <<"cmatch\n\n";
    mOfs<<std::right << std::setw(3)<< mAtomToConsider.size()
       <<std::right << std::setw(3)<< mBondToConsider.size()
      <<std::right << std::setw(3)<<"0"
     <<std::right << std::setw(3)<<"0"
    <<std::right << std::setw(3)<<"0"//Chiral
    <<std::right << std::setw(3)<<"0"//number of stext entries
    <<std::right << std::setw(12)<<" "//obsolete
    <<std::right << std::setw(3)<<"999"//Number of lins of additional properties
    <<" V2000\n";
}

void protspace::WriterSDF::outputAtom(const MacroMole &pMolecule)
try{

    for(size_t iAtm=0;iAtm < mAtomToConsider.size();++iAtm)
    {
        const MMAtom& atom=pMolecule.getAtom(mAtomToConsider.at(iAtm));


        mOfs<< std::right << std::setw(10) <<std::fixed<<std::setprecision(4)<<atom.pos().x()
            << std::right << std::setw(10) <<std::fixed<<std::setprecision(4)<<atom.pos().y()
            << std::right << std::setw(10) <<std::fixed<<std::setprecision(4)<<atom.pos().z();

        mOfs<<" ";
        mOfs << std::left<<std::setw(3)<<atom.getElement()
             << std::setw(3)<<"0";
        switch(atom.getFormalCharge())
        {
        case  3: mOfs<<std::setw(3)<<"1";break;
        case  2: mOfs<<std::setw(3)<<"2";break;
        case  1: mOfs<<std::setw(3)<<"3";break;
        case  0: mOfs<<std::setw(3)<<"0";break;
        case -1: mOfs<<std::setw(3)<<"5";break;
        case -2: mOfs<<std::setw(3)<<"6";break;
        case -3: mOfs<<std::setw(3)<<"7";break;
        }
        mOfs<<std::setw(3)<<"0"//Number of implicit H
           <<std::setw(3)<<"0"// Stereo care box
          <<std::setw(3)<<"0"// Valence
         <<std::setw(3)<<"0";
        for(size_t i=0;i<6;++i)
            mOfs<<std::setw(3)<<"0";
        mOfs<<"\n";
    }
}catch(ProtExcept &e)
{
    assert(e.getId()!="030401");/// Atom shoud exist
    e.addHierarchy("writerSDF::outputAtom");
    throw;
}


void protspace::WriterSDF::outputBond(const MacroMole &pMolecule)
try{

    size_t pos1,pos2;
    for(size_t iBond=0; iBond < mBondToConsider.size();++iBond)
    {
        const MMBond& bond = pMolecule.getBond(mBondToConsider.at(iBond));
        if (!getAtomPos(bond.getAtom1().getMID(),pos1))continue;
        if (!getAtomPos(bond.getAtom2().getMID(),pos2))continue;
        mOfs  << std::left << std::setw(3) << pos1+1 << " "
              << std::left << std::setw(3) << pos2+1 << " ";

        const auto it=BOND::typeToSD.find(bond.getType());
        if (it==BOND::typeToSD.end())
            throw_line("480201","WriterSDF::outputBond",
                       "Unrecognized bond type");
        mOfs<<(*it).second <<"  0  0  0  0\n";

    }

}catch(ProtExcept &e)
{
    assert(e.getId()!="350501");
    if (e.getId()!="480201") e.addHierarchy("WriterSDF::outputBond");
    throw;
}

void protspace::WriterSDF::saveMaps()
{
    for(const auto& it:maps)
    {
        mOfs<<"> <"<<it.first<<">\n"<<it.second<<"\n\n";
    }
}

void protspace::WriterSDF::save(const MacroMole& pMolecule) throw(ProtExcept)
try{
    if (!mOfs.is_open())open();
    selectObjects(pMolecule);
    outputHeader(pMolecule);
    outputAtom(pMolecule);
    outputBond(pMolecule);
    if(!maps.empty())saveMaps();
    //mOfs<<"M END\n";
    mOfs<< "$$$$\n";


}catch(ProtExcept &e)
{
    e.addHierarchy("WriterSDF::save");
    throw;
}

