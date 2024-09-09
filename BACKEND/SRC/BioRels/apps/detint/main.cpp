#include "headers/statics/argcv.h"
#include "detint.h"
#include "headers/proc/atomperception.h"
#include "headers/statics/logger.h"
#include "headers/proc/bondperception.h"
#include "headers/parser/readMOL2.h"
#include "headers/iwcore/macrotoiw.h"
#include "headers/parser/readPDB.h"
#include "headers/parser/readSDF.h"
#include "headers/parser/readers.h"
void help()
{
    std::cout <<"(1) usage: detint <option> -p PROTEIN -l LIGAND OUTPUT_NAME\n"<<
    "(2) usage: detint <option> -p PROTEIN -lm MULTI_LIGAND OUTPUT_NAME\n"<<
    "(3) usage: detint <option> -c COMPLEX OUTPUT_NAME\n"<<
    "-p   [MOL2|PDB]     Protein File in either PDB or MOL2 format (1)(2)\n"<<
    "-l   [MOL2|PDB|SDF] Ligand File in either PDB or MOL2 format  (1)\n"<<
    "-lm  [MOL2|SDF]     File containing multiple molecules (2)\n"
    "-c   [MOL2|PDB] Protein/Ligand complex in either PDB or MOL2 format(3)\n"<<
    "-ea                 Export aromatic/aromatic geometric rules (lots of files)\n"
    "\n"<<
    "OPTIONS:\n"<<
    "-n [NAME]       Name to be used in text results file (1)(3)\n"<<
    "-OF             Output fingerprint interaction (2)\n"
    "-OM             Output interaction in a molecular format (1)(2)(3)\n"<<
    "-OT             Output interaction in a Tab delimited format (1)(2)(3)\n"<<
    "-a              Results will be put at the end of the file (1)(2)(3)\n";

    ;
}



void protLig(const protspace::Argcv& args,
             const std::string& pProt,
             const std::string& pLig,
             const bool& forceName)
{
    try{
       DetInt detInt;
        detInt.prepMole(pProt);
        std::string pName="";
        detInt.assignLigand(pLig);
        if (args.getOptArg("-n",pName)) detInt.setCurrName(pName);
        detInt.calcProtLigInteractions();
        if (forceName)detInt.forceName();
        if (args.hasOption("-OM"))detInt.exportToMole(args.getArg(0));
        if (args.hasOption("-OT"))detInt.exportToCSV(args.getArg(0),
                                              pName);
    }catch(ProtExcept &e)
    {
        e.addHierarchy("MAIN::protLig");
        throw;
    }
}



void complex(const protspace::Argcv& args, const std::string& pComp)
{
    try{
        DetInt detInt;
        detInt.prepMole(pComp);

        std::string pName="";
        if (args.getOptArg("-n",pName)) detInt.setCurrName(pName);
        if (args.hasOption("-ea"))detInt.setExportArom(true);
        detInt.calcComplexInteractions();

        if (args.hasOption("-OM"))detInt.exportToMole(args.getArg(0));
        if (args.hasOption("-OT"))detInt.exportToCSV(args.getArg(0),
                                                     pName,args.hasOption("-a"));
    }catch(ProtExcept &e)
    {
        e.addHierarchy("MAIN::Complex");
        throw;
    }
}



void multiMole(const protspace::Argcv& args, const std::string& pProt, const std::string& pLig)
{
    try{

    DetInt detInt;
        protspace::AtomPerception aperc;
        protspace::BondPerception bperc;
        detInt.prepMole(pProt);
        std::string pFGPFile="";
        const bool wFGP(args.getOptArg("-f",pFGPFile));
        bool first=true;

        bool append=false;
        const bool hasAppOpt(args.hasOption("-a"));
        const bool forceName(args.hasOption("-fn"));
        const bool noid(!args.hasOption("-nid"));
        const bool exportMOLE(args.hasOption("-OM"));
        const bool exportCSV(args.hasOption("-OT"));
        const bool exportFGP(args.hasOption("-OF"));
        const std::string& pOutFile(args.getArg(0));

        protspace::ReaderBase* reader=nullptr;
        protspace::createReader(reader,pLig);
        const std::string ext(protspace::getExtension(pLig));
        do
        {

                protspace::MacroMole ligand;
                reader->load(ligand);
//                std::cout <<ligand.getName()<<"\n";
            try {
                MacroToIW miw(ligand);
                miw.generateRings();
                if (ext != "mol2" && ext != "MOL2") {
                    if (ext=="pdb" || ext=="PDB")bperc.processMolecule(ligand);

                    aperc.perceive(ligand);
                }
                if (ligand.numAtoms()==0)continue;
                if (!first && hasAppOpt)append = true;
                if (first)first=false;

                detInt.assignLigand(ligand);
                detInt.calcProtLigInteractions();
                if (forceName) detInt.forceName();
                if (exportCSV)detInt.exportToCSV(pOutFile, ligand.getName(), append,noid);
                if (exportMOLE)detInt.exportToMole(pOutFile);
                if (exportFGP)detInt.addFGP(ligand.getName());
            }catch(ProtExcept &e)
            {
                std::cout << "ERROR while loading molecule "<<ligand.getName()<<std::endl;
                std::cerr<<e.toString()<<std::endl;
            }
        }while (!reader->isEOF());
        if (exportFGP) detInt.exportToFGP(pOutFile+"_FGP.csv");


        delete reader;

    }catch(ProtExcept &E)
    {
        E.addHierarchy("MAIN::MultiMole");
        throw;
    }

}



/**
 * @brief main
 * @param argc
 * @param argv
 * @return
 * @throw 050002    ARGCV::ARGCV        Unexpected parameter
 * @throw 050001    ARGCV::ARGCV        not enough arguments
 * @throw 050101   Argcv::processOption   Expected value for option. Got nothing.
 * @throw 050102   Argcv::processOption   Expected value for option
 */
int main(const int argc, const char* argv[])
{
    try{
        ELOG("START")
                ELOG_FLUSH
        protspace::Argcv args(" -s -OM -OT -OF -nid -w  -fn -a -ea ",
                              " -f -p -l -c -n -lm ",
                              argc,argv,help, false,1);

        if (args.numArgs()==0)
        {
            help();
            return 0;
        }

        std::string pProtFile="";
        std::string pLigFile="";
        std::string pComplexFile="";
        std::string pMultiLig="";

        const bool hasProt(args.getOptArg("-p",pProtFile));
        const bool hasLig(args.getOptArg("-l",pLigFile));
        const bool hasMultipleLig(args.getOptArg("-lm",pMultiLig));
        const bool hasComplex(args.getOptArg("-c",pComplexFile));
        const bool forceName(args.hasOption("-fn"));
        if      (hasProt && hasLig) protLig(args,pProtFile,pLigFile,forceName);
        else if (hasMultipleLig && hasProt) multiMole(args,pProtFile,pMultiLig);
        else if (hasComplex)complex(args,pComplexFile);

ELOG("END")
        ELOG_FLUSH
    }catch(ProtExcept &e)
    {
        LOG_FLUSH
        ELOG_FLUSH
        std::cerr<<e.toString();
        return EXIT_FAILURE;
    }
    LOG_FLUSH
    ELOG_FLUSH
    return EXIT_SUCCESS;
}
